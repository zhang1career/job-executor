<?php

namespace App\Jobs;

use App\Services\DiscoverService;
use Paganini\XxlJobExecutor\Attributes\XxlJob;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;

class ServiceManagement
{
    /**
     * @return array [bool success, mixed data, string|null errorMessage]
     * @throws BindingResolutionException
     */
    #[XxlJob('discoverService')]
    public static function discover(): array
    {
        $discoverService = app()->make(DiscoverService::class);
        try {
            $result = $discoverService->discover();
            Log::debug('[servman] discover completed, result=', $result);
            return [true, $result, null];
        } catch (Exception $e) {
            Log::error('[servman] discover failed, error=' . $e->getMessage());
            return [false, null, $e->getMessage()];
        }
    }
}
