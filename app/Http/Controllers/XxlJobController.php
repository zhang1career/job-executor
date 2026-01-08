<?php

namespace App\Http\Controllers;

use App\Components\XxlResponse;
use App\Queues\XxlJobExecutor;
use App\Services\Adapters\XxlJobStorageFileLockAdapter;
use App\Services\XxlJobRegistry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Paganini\XxlJobExecutor\JobFileLock;
use Paganini\XxlJobExecutor\JobRequest;
use Paganini\XxlJobExecutor\JobRequestHandler;

class XxlJobController
{
    public function __construct(
        private readonly XxlJobRegistry $jobRegistry
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
        $requestData = Request::post();
        Log::debug('[xxljob] param: request=', $requestData);

        // Create job request from array
        $requestJob = JobRequest::fromArray($requestData);

        // Create request handler with dependencies
        $fileLock = new JobFileLock(new XxlJobStorageFileLockAdapter());
        $requestHandler = new JobRequestHandler(
            $this->jobRegistry,
            $fileLock
        );

        // Handle request
        $acceptedJob = $requestHandler->handle($requestJob);
        if (!$acceptedJob->isSuccess()) {
            return XxlResponse::fail($acceptedJob->getMessage() ?? 'Job execution failed');
        }

        // Extract job information from response
        $jobData = $acceptedJob->getData();
        $job = $jobData['job'];
        $params = $jobData['params'];
        $logId = $jobData['logId'];
        $filePath = $jobData['filePath'];

        // Dispatch job to queue
        XxlJobExecutor::dispatch($job, $params, $logId, $filePath);

        return XxlResponse::success();
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
        $jobId = (string)$request['jobId'];

        // delete job file using adapter
        $fileLock = new JobFileLock(new XxlJobStorageFileLockAdapter());
        if (!$fileLock->exists($jobId)) {
            Log::info('[xxljob] job file not exists, jobId=' . $jobId);
            return XxlResponse::success(null, 'job file not exists, jobId=' . $jobId);
        }

        $fileLock->delete($jobId);
        Log::debug('[xxljob] job killed, jobId=' . $jobId);
        return XxlResponse::success();
    }
}
