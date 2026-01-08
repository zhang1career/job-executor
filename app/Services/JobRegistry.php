<?php

namespace App\Services;

use App\Attributes\XxlJob;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionMethod;

/**
 * Job Registry Service
 *
 * Automatically discovers and registers methods with XxlJob Attribute
 */
class JobRegistry
{
    /**
     * @var array<string, array{0: class-string, 1: string}> Registered jobs, format: ['handler' => [ClassName::class, 'methodName']]
     */
    private array $jobs = [];

    /**
     * Scan and register all methods with XxlJob Attribute
     *
     * @param string $jobPath Directory path where Job classes are located, relative to app directory
     * @return void
     */
    public function scanAndRegister(string $jobPath = 'Jobs'): void
    {
        $basePath = app_path($jobPath);
        if (!is_dir($basePath)) {
            Log::warning("[jobreg] job directory not found: {$basePath}");
            return;
        }

        $files = glob($basePath . '/*.php');
        foreach ($files as $file) {
            $this->scanFile($file, $jobPath);
        }

        Log::info("[jobreg] registered " . count($this->jobs) . " jobs");
    }

    /**
     * Scan a single file and register Job methods within it
     *
     * @param string $filePath Full path to the file
     * @param string $jobPath Directory path where Job classes are located, relative to app directory
     * @return void
     */
    private function scanFile(string $filePath, string $jobPath): void
    {
        $className = $this->getClassNameFromFile($filePath, $jobPath);

        if (!$className || !class_exists($className)) {
            return;
        }

        $reflection = new ReflectionClass($className);

        // Only scan public static methods
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC) as $method) {
            $attributes = $method->getAttributes(XxlJob::class);

            foreach ($attributes as $attribute) {
                /** @var XxlJob $xxlJob */
                $xxlJob = $attribute->newInstance();
                $handler = $xxlJob->handler;

                if (isset($this->jobs[$handler])) {
                    Log::warning("[jobreg] duplicate handler '{$handler}' found. Overwriting previous registration.");
                }

                $this->jobs[$handler] = [$className, $method->getName()];
                Log::debug("[jobreg] registered job: {$handler} => {$className}::{$method->getName()}");
            }
        }
    }

    /**
     * Get full class name from file path
     *
     * @param string $filePath Full path to the file
     * @param string $jobPath Directory path where Job classes are located, relative to app directory
     * @return string|null Full class name, or null if cannot be determined
     */
    private function getClassNameFromFile(string $filePath, string $jobPath): ?string
    {
        // Get path relative to app directory
        $appPath = app_path();
        if (!str_starts_with($filePath, $appPath)) {
            return null;
        }

        $relativePath = substr($filePath, strlen($appPath) + 1);
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace('/', '\\', $relativePath);

        // Build full class name (assuming PSR-4 standard)
        $className = 'App\\' . $relativePath;

        return $className;
    }

    /**
     * Get registered Job
     *
     * @param string $handler Job identifier
     * @return array{0: class-string, 1: string}|null Returns [ClassName::class, 'methodName'], or null if not found
     */
    public function getJob(string $handler): ?array
    {
        return $this->jobs[$handler] ?? null;
    }

    /**
     * Check if Job is registered
     *
     * @param string $handler Job identifier
     * @return bool
     */
    public function hasJob(string $handler): bool
    {
        return isset($this->jobs[$handler]);
    }

    /**
     * Get all registered Jobs
     *
     * @return array<string, array{0: class-string, 1: string}>
     */
    public function getAllJobs(): array
    {
        return $this->jobs;
    }

    /**
     * Manually register a Job (for backward compatibility or special cases)
     *
     * @param string $handler Job identifier
     * @param array{0: class-string, 1: string} $callable Callable object, format: [ClassName::class, 'methodName']
     * @return void
     */
    public function register(string $handler, array $callable): void
    {
        $this->jobs[$handler] = $callable;
        Log::debug("[jobreg] manually registered job: {$handler}");
    }
}

