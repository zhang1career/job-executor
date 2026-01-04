<?php

namespace App\Jobs;

use App\Services\ApiService;
use App\Utils\ExceptionUtil;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;

class RuleBroker
{
    /**
     * @throws BindingResolutionException
     */
    public static function login(): bool
    {
        $apiService = app()->make(ApiService::class);

        $user = config('services.api_gateway.account.rule-broker.username');
        $pass = config('services.api_gateway.account.rule-broker.password');
        if (!$user || !$pass) {
            Log::error('[rule-broker] login failed, missing environment variables for rule-broker account');
            return false;
        }

        try {
            $result = $apiService->login($user, $pass);
            if (!$result) {
                Log::error('[rule-broker] login failed');
                return false;
            }
            Log::debug('[rule-broker] login succeeded', ['result' => $result]);
            return true;
        } catch (Exception $e) {
            ExceptionUtil::logTrace('[rule-broker] login exception', $e);
            return false;
        }
    }

    public static function refresh()
    {
        $apiService = app()->make(ApiService::class);

        $user = config('services.api_gateway.account.rule-broker.username');
        if (!$user) {
            Log::error('[rule-broker] refresh token failed, missing environment variables for rule-broker account');
            return false;
        }

        try {
            $result = $apiService->refresh($user);
            if (!$result) {
                Log::error('[rule-broker] refresh token failed');
                return false;
            }
            Log::debug('[rule-broker] refresh token succeeded', ['result' => $result]);
            return true;
        } catch (Exception $e) {
            ExceptionUtil::logTrace('[rule-broker] refresh token exception', $e);
            return false;
        }
    }
}
