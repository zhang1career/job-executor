<?php

namespace App\Providers;

use App\Services\JobRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 注册 JobRegistry 为单例
        $this->app->singleton(JobRegistry::class, function ($app) {
            $registry = new JobRegistry();
            
            // 扫描并注册所有带有 XxlJob Attribute 的方法
            $registry->scanAndRegister('Jobs');
            
            // 兼容旧配置：从 config/xxl.php 中读取手动配置的 jobs
            $configJobs = config('xxl.jobs', []);
            foreach ($configJobs as $handler => $callable) {
                if (is_array($callable) && count($callable) === 2) {
                    $registry->register($handler, $callable);
                }
            }
            
            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
