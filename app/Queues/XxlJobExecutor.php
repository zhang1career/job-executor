<?php

namespace App\Queues;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class XxlJobExecutor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private array $callable;
    private mixed $param;
    private int $logId;
    private string $lock;


    /**
     * Create job instance with parameters
     */
    public function __construct(array $callable,
                                mixed $param,
                                int   $logId,
                                mixed $lock)
    {
        $this->callable = $callable;
        $this->param = $param;
        $this->logId = $logId;
        $this->lock = $lock;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        Log::debug('[xxl-job] callback start, logId=' . $this->logId);

        [$_ok, $data, $msg] = call_user_func($this->callable, $this->param);
        Storage::disk('local')->delete($this->lock);
        if (!$_ok) {
            $ok = $this->callbackError($msg);
            if (!$ok) {
                Log::error('[xxl-job] callbackError failed for logId=' . $this->logId);
            }
            return;
        }

        $ok = $this->callbackOk($data);
        if (!$ok) {
            Log::error('[xxl-job] callbackOk failed for logId=' . $this->logId);
        }
    }

    private function callbackOk(mixed $data): bool
    {
        try {
            return $this->sendCallback(200, json_encode($data));
        } catch (GuzzleException $e) {
            Log::error('[xxl-job] callbackOk exception of Guzzle: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            Log::error('[xxl-job] callbackOk exception: ' . $e->getMessage());
            return false;
        }
    }

    private function callbackError(string $msg): bool
    {
        try {
            return $this->sendCallback(500, $msg);
        } catch (GuzzleException $e) {
            Log::error('[xxl-job] callbackError exception of Guzzle: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            Log::error('[xxl-job] callbackError exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send callback request to scheduler
     *
     * @param int $handleCode Execution result code (200=success, 500=failure, 502=timeout)
     * @param string $handleMsg Execution result message
     * @return bool
     * @throws GuzzleException
     */
    private function sendCallback(int $handleCode, string $handleMsg): bool
    {
        $xxljobAdminUrl = config('xxl.admin_address');
        $xxljobAccessToken = config('xxl.token');
        $callbackUrl = $xxljobAdminUrl . '/api/callback';

        // prepare callback data
        $headers = [
            'Content-Type' => 'application/json',
            'XXL-JOB-ACCESS-TOKEN' => $xxljobAccessToken
        ];
        $requestBody = [
            [
                'logId' => $this->logId,
                'logDateTim' => (int)(microtime(true) * 1000),
                'handleCode' => $handleCode,
                'handleMsg' => $handleMsg
            ]
        ];

        // callback request
        $httpClient = new Client();
        $response = $httpClient->post($callbackUrl, [
            'headers' => $headers,
            'json' => $requestBody,
            'timeout' => 10
        ]);
        if ($response->getStatusCode() !== 200) {
            Log::error('[xxl-job] callback failed, status code: ' . $response->getStatusCode());
            return false;
        }

        return true;
    }
}
