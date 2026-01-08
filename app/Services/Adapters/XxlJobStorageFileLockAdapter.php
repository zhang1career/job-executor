<?php

namespace App\Services\Adapters;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Paganini\XxlJobExecutor\Interfaces\FileLockInterface;

/**
 * Storage File Lock Adapter
 *
 * Laravel-specific implementation of FileLockInterface using Storage facade
 */
class XxlJobStorageFileLockAdapter implements FileLockInterface
{
    private const JOB_PATH = 'jobs';
    private const JOB_FILE_SUFFIX = '.job';

    /**
     * Build job file path
     *
     * @param string $jobId Job ID
     * @return string File path
     */
    private function buildJobFilePath(string $jobId): string
    {
        $jobFileName = $jobId . self::JOB_FILE_SUFFIX;
        return self::JOB_PATH . '/' . $jobFileName;
    }

    /**
     * Create a job file lock
     *
     * @param string $jobId Job ID
     * @return string|null Returns file path if created successfully, null otherwise
     */
    public function create(string $jobId): ?string
    {
        $jobFilePath = $this->buildJobFilePath($jobId);
        $storage = Storage::disk('local');
        $has = $storage->put($jobFilePath, $jobId);
        if ($has === false) {
            Log::error("[xxljob] failed to create job file lock at path: {$jobFilePath}");
            return null;
        }

        Log::debug("[xxljob] creating job file lock at path: {$jobFilePath}");
        return $jobFilePath;
    }

    /**
     * Delete a job file lock
     *
     * @param string $jobId Job ID
     * @return bool True if deleted successfully, false otherwise
     */
    public function delete(string $jobId): bool
    {
        $jobFilePath = $this->buildJobFilePath($jobId);
        $storage = Storage::disk('local');

        if (!$storage->exists($jobFilePath)) {
            Log::warning("[xxljob] job file lock not found at path: {$jobFilePath} when trying to delete");
            return true; // Already deleted, consider it success
        }

        $ret = $storage->delete($jobFilePath);
        Log::debug("[xxljob] deleting job file lock at path: {$jobFilePath}, success=" . ($ret ? 'true' : 'false'));
        return $ret;
    }

    /**
     * Check if a job file lock exists
     *
     * @param string $jobId Job ID
     * @return bool True if lock exists, false otherwise
     */
    public function exists(string $jobId): bool
    {
        $jobFilePath = $this->buildJobFilePath($jobId);
        $storage = Storage::disk('local');
        $ret = $storage->exists($jobFilePath);
        Log::debug("[xxljob] checking existence of job file lock at path: {$jobFilePath}, exists=" . ($ret ? 'true' : 'false'));
        return $ret;
    }
}

