<?php

namespace App\Jobs;

use App\Services\DiscoverService;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;

class ServiceManagement
{
    /**
     * @throws BindingResolutionException
     */
    public static function discover(): bool
    {
        $discoverService = app()->make(DiscoverService::class);
        try {
            $result = $discoverService->discover();
            Log::debug('[servman] discover completed, result=', $result);
            return true;
        } catch (Exception $e) {
            Log::error('[servman] discover failed, error=' . $e->getMessage());
            return false;
        }
    }
}
