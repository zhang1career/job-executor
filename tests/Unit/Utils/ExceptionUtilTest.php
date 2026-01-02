<?php

namespace Tests\Unit\Utils;

use App\Utils\ExceptionUtil;
use Exception;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ExceptionUtilTest extends TestCase
{
    public function test_logTrace(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::on(function ($message) {
                return str_contains($message, 'Test error')
                    && str_contains($message, 'errcode=')
                    && str_contains($message, 'errmsg=')
                    && str_contains($message, 'trace[0]=');
            }));

        $exception = new Exception('Test error message', 500);
        ExceptionUtil::logTrace('Test error', $exception);
    }

    public function test_logTrace_with_custom_code(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::on(function ($message) {
                return str_contains($message, 'errcode=404');
            }));

        $exception = new Exception('Not found', 404);
        ExceptionUtil::logTrace('Resource not found', $exception);
    }
}

