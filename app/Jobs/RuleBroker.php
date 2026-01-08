<?php

namespace App\Jobs;

use Paganini\XxlJobExecutor\Attributes\XxlJob;
use App\Services\ApiService;
use App\Utils\ExceptionUtil;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;

class RuleBroker
{
    /**
     * @return array [bool success, mixed data, string|null errorMessage]
     * @throws BindingResolutionException
     */
    #[XxlJob('loginRuleBroker')]
    public static function login(): array
    {
        $apiService = app()->make(ApiService::class);

        $user = config('services.api_gateway.account.rule-broker.username');
        $pass = config('services.api_gateway.account.rule-broker.password');
        if (!$user || !$pass) {
            Log::error('[rule-broker] login failed, missing environment variables for rule-broker account');
            return [false, null, 'login failed, missing environment variables for rule-broker account'];
        }

        try {
            $result = $apiService->login($user, $pass);
            if (!$result) {
                Log::error('[rule-broker] login failed');
                return [false, null, 'login failed'];
            }
            Log::debug('[rule-broker] login succeeded', ['result' => $result]);
            return [true, $result, null];
        } catch (Exception $e) {
            ExceptionUtil::logTrace('[rule-broker] login exception', $e);
            return [false, null, $e->getMessage()];
        }
    }

    /**
     * @return array [bool success, mixed data, string|null errorMessage]
     * @throws BindingResolutionException
     */
    #[XxlJob('refreshRuleBroker')]
    public static function refresh(): array
    {
        $apiService = app()->make(ApiService::class);

        $user = config('services.api_gateway.account.rule-broker.username');
        if (!$user) {
            Log::error('[rule-broker] refresh token failed, missing environment variables for rule-broker account');
            return [false, null, 'refresh token failed, missing environment variables for rule-broker account'];
        }

        try {
            $result = $apiService->refresh($user);
            if (!$result) {
                Log::error('[rule-broker] refresh token failed');
                return [false, null, 'refresh token failed'];
            }
            Log::debug('[rule-broker] refresh token succeeded', ['result' => $result]);
            return [true, null, null];
        } catch (Exception $e) {
            ExceptionUtil::logTrace('[rule-broker] refresh token exception', $e);
            return [false, null, $e->getMessage()];
        }
    }
}
