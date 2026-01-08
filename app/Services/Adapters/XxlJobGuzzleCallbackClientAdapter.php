<?php

namespace App\Services\Adapters;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Paganini\XxlJobExecutor\Interfaces\CallbackClientInterface;

/**
 * Guzzle Callback Client Adapter
 *
 * Laravel-specific implementation of CallbackClientInterface using Guzzle HTTP client
 */
class XxlJobGuzzleCallbackClientAdapter implements CallbackClientInterface
{
    public function __construct(
        private readonly string $adminAddress,
        private readonly string $accessToken,
        private readonly int $timeout = 10
    ) {
    }

    /**
     * Send callback request to scheduler
     *
     * @param int $logId Current scheduling log ID
     * @param int $handleCode Execution result code (200=success, 500=failure, 502=timeout)
     * @param string $handleMsg Execution result message
     * @return bool True if callback was sent successfully, false otherwise
     */
    public function sendCallback(int $logId, int $handleCode, string $handleMsg): bool
    {
        try {
            $callbackUrl = $this->adminAddress . '/api/callback';

            $headers = [
                'Content-Type' => 'application/json',
                'XXL-JOB-ACCESS-TOKEN' => $this->accessToken
            ];

            $requestBody = [
                [
                    'logId' => $logId,
                    'logDateTim' => (int)(microtime(true) * 1000),
                    'handleCode' => $handleCode,
                    'handleMsg' => $handleMsg
                ]
            ];

            $httpClient = new Client();
            $response = $httpClient->post($callbackUrl, [
                'headers' => $headers,
                'json' => $requestBody,
                'timeout' => $this->timeout
            ]);

            if ($response->getStatusCode() !== 200) {
                Log::error('[xxljob] callback failed, status code: ' . $response->getStatusCode());
                return false;
            }

            return true;
        } catch (GuzzleException $e) {
            Log::error('[xxljob] callback exception of Guzzle: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            Log::error('[xxljob] callback exception: ' . $e->getMessage());
            return false;
        }
    }
}

