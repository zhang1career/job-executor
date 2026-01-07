<?php

namespace App\Services;

use App\Utils\ExceptionUtil;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Paganini\Utils\ApiGatewayUtil;

class ApiService
{

    const REFRESH_TOKEN = 'refresh_token';

    private Client $client;

    private string $loginUrl;


    public function __construct()
    {
        $this->client = new Client(['timeout' => 10]);
        $_baseUrl = config('services.api_gateway.base_url');
        $this->loginUrl = rtrim($_baseUrl, '/') . '/consumer/login';
    }

    public function login(string $user, string $pass): array|null
    {
        try {
            $response = $this->client->post($this->loginUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'username' => $user,
                    'password' => $pass,
                ],
            ]);

            $status = $response->getStatusCode();
            if ($status !== 200) {
                Log::error("[account] login failed with status code: $status");
                return null;
            }

            $body = $response->getBody()->getContents();
            if (!$body) {
                Log::error('[account] login failed: empty response body');
                return null;
            }

            $tokens = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($tokens)) {
                Log::error('[account] login failed: invalid JSON response', [
                    'json_error' => json_last_error_msg(),
                    'response_body' => substr($body, 0, 500), // 只记录前500字符避免日志过长
                ]);
                return null;
            }
            // store tokens in Redis
            $writtenKeys = ApiGatewayUtil::saveTokens(Redis::connection(), $user, $tokens);

            return [
                'redis_keys_written' => $writtenKeys,
                'scope' => $tokens['scope'] ?? null,
            ];
        } catch (GuzzleException $e) {
            ExceptionUtil::logTrace('[account] login failed', $e);
            return null;
        }
    }

    public function refresh(string $user): array|null
    {
        try {
            $refreshToken = ApiGatewayUtil::getRefreshToken(Redis::connection(), $user);
            if (!$refreshToken) {
                Log::error("[account] no refresh token found in Redis for user: $user");
                return null;
            }

            $response = $this->client->put($this->loginUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'json' => [
                    self::REFRESH_TOKEN => $refreshToken,
                ],
            ]);
            $status = $response->getStatusCode();
            if ($status !== 200) {
                Log::error("[account] token refresh failed with status code: $status");
                return null;
            }
            $body = $response->getBody()->getContents();
            if (!$body) {
                Log::error('[account] token refresh failed: empty response body');
                return null;
            }
            $tokens = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($tokens)) {
                Log::error('[account] token refresh failed: invalid JSON response', [
                    'json_error' => json_last_error_msg(),
                    'response_body' => substr($body, 0, 500), // 只记录前500字符避免日志过长
                ]);
                return null;
            }

            // store tokens in Redis
            $writtenKeys = ApiGatewayUtil::saveTokens(Redis::connection(), $user, $tokens);

            return [
                'redis_keys_written' => $writtenKeys,
            ];
        } catch (GuzzleException $e) {
            ExceptionUtil::logTrace('[account] token refresh failed', $e);
            return null;
        }
    }
}
