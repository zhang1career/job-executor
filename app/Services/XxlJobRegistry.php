<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Paganini\XxlJobExecutor\Attributes\XxlJob;
use Paganini\XxlJobExecutor\JobRegistry as PaganiniXxlJobRegistry;
use ReflectionClass;
use ReflectionMethod;

/**
 * Job Registry Service
 *
 * Laravel-specific wrapper that extends Paganini JobRegistry
 * Adds scanning functionality for XxlJob attributes
 */
class XxlJobRegistry extends PaganiniXxlJobRegistry
{
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

        Log::info("[jobreg] registered " . count($this->getAllJobs()) . " jobs");
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
            Log::warning("[jobreg] class not found for file: {$filePath}");
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

                if ($this->hasJob($handler)) {
                    Log::warning("[jobreg] duplicate handler '{$handler}' found. Overwriting previous registration.");
                }

                parent::register($handler, [$className, $method->getName()]);
                Log::debug("[jobreg] auto registered job: {$handler} => {$className}::{$method->getName()}");
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
     * Manually register a Job (for backward compatibility or special cases)
     *
     * @param string $handler Job identifier
     * @param array{0: class-string, 1: string} $callable Callable object, format: [ClassName::class, 'methodName']
     * @return void
     */
    public function register(string $handler, array $callable): void
    {
        parent::register($handler, $callable);
        Log::debug("[jobreg] manually registered job: {$handler}");
    }
}

