<?php

namespace App\Http\Controllers;

use App\Components\XxlResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class XxlJobController
{
    const JOB_PATH = 'jobs';
    const JOB_FILE_SUFFIX = '.job';


    /**
     * 执行器心跳
     * @return array
     */
    public function beat(): array
    {
        return XxlResponse::success();
    }


    /**
     * 执行任务 api
     * {
     *   "jobId":1,                                  // 任务ID
     *   "executorHandler":"demoJobHandler",         // 任务标识
     *   "executorParams":"demoJobHandler",          // 任务参数
     *   "executorBlockStrategy":"COVER_EARLY",      // 任务阻塞策略，可选值参考 com.xxl.job.core.enums.ExecutorBlockStrategyEnum
     *   "executorTimeout":0,                        // 任务超时时间，单位秒，大于零时生效
     *   "logId":1,                                  // 本次调度日志ID
     *   "logDateTime":1586629003729,                // 本次调度日志时间
     *   "glueType":"BEAN",                          // 任务模式，可选值参考 com.xxl.job.core.glue.GlueTypeEnum
     *   "glueSource":"xxx",                         // GLUE脚本代码
     *   "glueUpdatetime":1586629003727,             // GLUE脚本更新时间，用于判定脚本是否变更以及是否需要刷新
     *   "broadcastIndex":0,                         // 分片参数：当前分片
     *   "broadcastTotal":0                          // 分片参数：总分片
     * }
     * @return array
     */
    public function run()
    {
        // get request param
        $request = Request::post();
        Log::debug('[xxljob] param: request=', $request);
        $jobId = $request['jobId'];
        $executorHandler = $request['executorHandler'];
        $executorParams = $request['executorParams'];
        $logId = $request['logId'];

        // create job file
        $jobFilePath = $this->buildJobFilePath($jobId);
        $storage = Storage::disk('local');
        $has = $storage->put($jobFilePath, $jobId);
        if ($has === false) {
            Log::error('[xxljob] failed, cannot create job file, filepath=' . $jobFilePath);
            return XxlResponse::fail('creating job file failed! filepath=' . $jobFilePath);
        }
        // get config info
        $jobs = config('xxl')['jobs'];
        if (!isset($jobs[$executorHandler])) {
            $storage->delete($jobFilePath);
            Log::error('[xxljob] failed, executor handler not configured! handler=' . $executorHandler);
            return XxlResponse::fail('executor handler not configured! handler=' . $executorHandler);
        }
        $objCall = $jobs[$executorHandler];
        if (!$objCall) {
            $storage->delete($jobFilePath);
            Log::error('[xxljob] failed, executor handler invalid! handler=' . $executorHandler);
            return XxlResponse::fail('executor handler invalid! handler=' . $executorHandler);
        }

        $ok = call_user_func($objCall, $executorParams);
        if (!$ok) {
            $storage->delete($jobFilePath);
            return XxlResponse::jobCallback($logId, 500, 'failed!');
        }

        $storage->delete($jobFilePath);
        return XxlResponse::jobCallback($logId, 200, 'success');
    }

    /**
     * @param mixed $jobId
     * @return string
     */
    private function buildJobFilePath(mixed $jobId): string
    {
        $jobFileName = $jobId . self::JOB_FILE_SUFFIX;
        return self::JOB_PATH . '/' . $jobFileName;
    }


    /**
     * 说明：终止任务
     * ------
     * 地址格式：{执行器内嵌服务跟地址}/kill
     * Header：
     * XXL-JOB-ACCESS-TOKEN : {请求令牌}
     * 请求数据格式如下，放置在 RequestBody 中，JSON格式：
     * {
     * "jobId":1       // 任务ID
     * }
     * 响应数据格式：
     * {
     * "code": 200,      // 200 表示正常、其他失败
     * "msg": null       // 错误提示消息
     * }
     */
    public function kill()
    {
        // get request param
        $request = Request::post();
        Log::debug('[xxljob] kill param: request=', $request);
        $jobId = $request['jobId'];

        // delete job file
        $jobFilePath = $this->buildJobFilePath($jobId);
        $storage = Storage::disk('local');
        if (!$storage->exists($jobFilePath)) {
            Log::info('[xxljob] job file not exists, jobId=' . $jobId);
            return XxlResponse::success(null, 'job file not exists, jobId=' . $jobId);
        }

        $storage->delete($jobFilePath);
        Log::debug('[xxljob] job killed, jobId=' . $jobId);
        return XxlResponse::success();
    }
}
