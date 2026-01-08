<?php

namespace App\Queues;

use App\Services\Adapters\XxlJobGuzzleCallbackClientAdapter;
use App\Services\Adapters\XxlJobStorageFileLockAdapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Paganini\XxlJobExecutor\JobExecutionHandler;

/**
 * XxlJobExecutor
 *
 * Laravel Queue job that wraps Paganini JobExecutionHandler
 * Handles asynchronous job execution
 */
class XxlJobExecutor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $callable;
    private mixed $param;
    private int $logId;
    private string $jobId;

    /**
     * Create job instance with parameters
     */
    public function __construct(
        array $callable,
        mixed $param,
        int $logId,
        string $filePath
    ) {
        $this->callable = $callable;
        $this->param = $param;
        $this->logId = $logId;
        // Extract jobId from filePath (e.g., "jobs/123.job" -> "123")
        $this->jobId = basename($filePath, '.job');
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        Log::debug('[xxljob] callback start, logId=' . $this->logId);

        // Create execution handler with adapters
        $executionHandler = new JobExecutionHandler(
            new XxlJobGuzzleCallbackClientAdapter(
                config('xxl.admin_address'),
                config('xxl.token')
            ),
            new XxlJobStorageFileLockAdapter()
        );

        // Execute job using handler
        $executionHandler->execute(
            $this->callable,
            $this->param,
            $this->logId,
            $this->jobId
        );
    }
}
