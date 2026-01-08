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
        // Register JobRegistry as singleton
        $this->app->singleton(JobRegistry::class, function ($app) {
            $registry = new JobRegistry();
            
            // Scan and register all methods with XxlJob Attribute
            $registry->scanAndRegister('Jobs');
            
            // Backward compatibility: read manually configured jobs from config/xxl.php
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
