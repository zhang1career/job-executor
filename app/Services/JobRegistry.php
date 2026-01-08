<?php

namespace App\Services;

use App\Attributes\XxlJob;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionMethod;

/**
 * Job Registry Service
 *
 * 自动发现和注册带有 XxlJob Attribute 的方法
 */
class JobRegistry
{
    /**
     * @var array<string, array{0: class-string, 1: string}> 已注册的 jobs，格式：['handler' => [ClassName::class, 'methodName']]
     */
    private array $jobs = [];

    /**
     * 扫描并注册所有带有 XxlJob Attribute 的方法
     *
     * @param string $jobPath Job 类所在的目录路径，相对于 app 目录
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
     * 扫描单个文件并注册其中的 Job 方法
     *
     * @param string $filePath 文件完整路径
     * @param string $jobPath Job 类所在的目录路径，相对于 app 目录
     * @return void
     */
    private function scanFile(string $filePath, string $jobPath): void
    {
        $className = $this->getClassNameFromFile($filePath, $jobPath);

        if (!$className || !class_exists($className)) {
            return;
        }

        $reflection = new ReflectionClass($className);

        // 只扫描公共静态方法
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
     * 从文件路径获取完整的类名
     *
     * @param string $filePath 文件完整路径
     * @param string $jobPath Job 类所在的目录路径，相对于 app 目录
     * @return string|null 完整的类名，如果无法确定则返回 null
     */
    private function getClassNameFromFile(string $filePath, string $jobPath): ?string
    {
        // 获取相对于 app 目录的路径
        $appPath = app_path();
        if (!str_starts_with($filePath, $appPath)) {
            return null;
        }

        $relativePath = substr($filePath, strlen($appPath) + 1);
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace('/', '\\', $relativePath);

        // 构建完整的类名（假设遵循 PSR-4 标准）
        $className = 'App\\' . $relativePath;

        return $className;
    }

    /**
     * 获取已注册的 Job
     *
     * @param string $handler 任务标识
     * @return array{0: class-string, 1: string}|null 返回 [ClassName::class, 'methodName']，如果不存在则返回 null
     */
    public function getJob(string $handler): ?array
    {
        return $this->jobs[$handler] ?? null;
    }

    /**
     * 检查 Job 是否已注册
     *
     * @param string $handler 任务标识
     * @return bool
     */
    public function hasJob(string $handler): bool
    {
        return isset($this->jobs[$handler]);
    }

    /**
     * 获取所有已注册的 Jobs
     *
     * @return array<string, array{0: class-string, 1: string}>
     */
    public function getAllJobs(): array
    {
        return $this->jobs;
    }

    /**
     * 手动注册一个 Job（用于向后兼容或特殊情况）
     *
     * @param string $handler 任务标识
     * @param array{0: class-string, 1: string} $callable 可调用对象，格式：[ClassName::class, 'methodName']
     * @return void
     */
    public function register(string $handler, array $callable): void
    {
        $this->jobs[$handler] = $callable;
        Log::debug("[jobreg] manually registered job: {$handler}");
    }
}

