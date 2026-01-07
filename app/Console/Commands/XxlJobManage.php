<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class XxlJobManage extends Command implements PromptsForMissingInput
{
    /**
     * 命令名称及签名.
     *
     * @var string
     */
    protected $signature = 'cmd:xxljob
                            {action : The action to perform, e.g. register.}';
    /**
     * 命令描述.
     *
     * @var string
     */
    protected $description = 'XXL-Job management command';

    /**
     * http客户端
     * @var Client
     */
    protected Client $httpClient;

    /**
     * xxljob-admin 地址
     * @var string
     */
    protected string $xxljobAdminUrl;

    /**
     * 本机IP
     * @var string
     */
    protected string $localIp;

    /**
     * 创建命令.
     *
     * @return void
     */
    public function __construct()
    {
        $this->httpClient = new Client();
        $this->xxljobAdminUrl = config('xxl.admin_address');
        $this->localIp = config('xxl.local_ip');
        parent::__construct();
    }

    /**
     * 提示缺失的输入参数.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing() : array {
        return [
            'action' => ['Which action do you want to perform?', 'register'],
        ];
    }

    /**
     * 执行命令.
     *
     * @throws GuzzleException
     */
    public function handle(): void
    {
        $token = config('xxl.token');
        if (!$token) {
            $this->error('XXL-JOB token is not configured');
            return;
        }

        $response = null;
        if ($this->argument('action') == 'register') {
            $response = $this->register($token);
        }
        if (!$response) {
            $this->error('Invalid action');
            return;
        }
        Log::info('[cmd.xxljob] result=' . $response->getBody()->getContents());
    }

    /**
     * @param mixed $token
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function register(mixed $token): ResponseInterface
    {
        $uri = $this->xxljobAdminUrl . '/api/registry';

        return $this->httpClient->post($uri, [
            'headers' => [
                'XXL-JOB-ACCESS-TOKEN' => $token
            ],
            'json' => [
                'registryGroup' => 'EXECUTOR',
                'registryKey' => 'dataExecutor',
                'registryValue' => $this->localIp
            ]
        ]);
    }
}
