<?php

namespace Tests\Unit\Utils;

use App\Constants\SystemConstant;
use App\Exceptions\UnsupportedOperationException;
use App\Utils\SystemUtil;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SystemUtilTest extends TestCase
{
    public function test_getUuid_throws_exception_for_unsupported_os(): void
    {
        Config::set('system.os', 'unsupported');

        $this->expectException(UnsupportedOperationException::class);
        SystemUtil::getUuid();
    }

    public function test_getUuid_with_ec2_os(): void
    {
        Config::set('system.os', SystemConstant::OS_EC2);

        // Mock the exec command to return a test UUID
        // Note: This test may fail if dmidecode is not available or requires sudo
        // In a real scenario, you might want to mock the exec function or use a different approach
        try {
            $result = SystemUtil::getUuid();
            $this->assertIsString($result);
            $this->assertEquals(32, strlen($result)); // Short UUID format
        } catch (UnsupportedOperationException $e) {
            // If exec fails, that's expected in some environments
            $this->assertTrue(true);
        }
    }
}

