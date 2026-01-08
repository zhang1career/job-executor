<?php

namespace App\Http\Controllers;

use App\Components\XxlResponse;
use App\Queues\XxlJobExecutor;
use App\Services\JobRegistry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class XxlJobController
{
    const JOB_PATH = 'jobs';
    const JOB_FILE_SUFFIX = '.job';

    public function __construct(
        private readonly JobRegistry $jobRegistry
    ) {
    }


    /**
     * Executor heartbeat
     * @return array
     */
    public function beat(): array
    {
        return XxlResponse::success();
    }


    /**
     * Execute job API
     * {
     *   "jobId":1,                                  // Job ID
     *   "executorHandler":"demoJobHandler",         // Job identifier
     *   "executorParams":"demoJobHandler",          // Job parameters
     *   "executorBlockStrategy":"COVER_EARLY",      // Job blocking strategy, see com.xxl.job.core.enums.ExecutorBlockStrategyEnum
     *   "executorTimeout":0,                        // Job timeout in seconds, effective when greater than zero
     *   "logId":1,                                  // Current scheduling log ID
     *   "logDateTime":1586629003729,                // Current scheduling log timestamp
     *   "glueType":"BEAN",                          // Job mode, see com.xxl.job.core.glue.GlueTypeEnum
     *   "glueSource":"xxx",                         // GLUE script code
     *   "glueUpdatetime":1586629003727,             // GLUE script update time, used to determine if script changed and needs refresh
     *   "broadcastIndex":0,                         // Sharding parameter: current shard
     *   "broadcastTotal":0                          // Sharding parameter: total shards
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

        // get job from registry
        $job = $this->jobRegistry->getJob($executorHandler);
        if (!$job) {
            $storage->delete($jobFilePath);
            Log::error('[xxljob] failed, executor handler not registered! handler=' . $executorHandler);
            return XxlResponse::fail('executor handler not registered! handler=' . $executorHandler);
        }

        // dispatch job
        XxlJobExecutor::dispatch($job, $executorParams, $logId, $jobFilePath);

        return XxlResponse::success();
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
     * Kill job
     * ------
     * URL format: {executor embedded service base URL}/kill
     * Header:
     * XXL-JOB-ACCESS-TOKEN : {request token}
     * Request data format (JSON in RequestBody):
     * {
     * "jobId":1       // Job ID
     * }
     * Response data format:
     * {
     * "code": 200,      // 200 means success, others mean failure
     * "msg": null       // Error message
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
