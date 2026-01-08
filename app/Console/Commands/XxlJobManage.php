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
     * Command name and signature.
     *
     * @var string
     */
    protected $signature = 'cmd:xxljob
                            {action : The action to perform, e.g. register.}';
    /**
     * Command description.
     *
     * @var string
     */
    protected $description = 'XXL-Job management command';

    /**
     * HTTP client
     * @var Client
     */
    protected Client $httpClient;

    /**
     * xxljob-admin address
     * @var string
     */
    protected string $xxljobAdminUrl;

    /**
     * Local IP address
     * @var string
     */
    protected string $localIp;

    /**
     * Create command instance.
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
     * Prompt for missing input arguments.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing() : array {
        return [
            'action' => ['Which action do you want to perform?', 'register'],
        ];
    }

    /**
     * Execute the command.
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
