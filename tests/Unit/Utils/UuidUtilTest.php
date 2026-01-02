<?php

namespace Tests\Unit\Utils;

use App\Utils\UuidUtil;
use Tests\TestCase;

class UuidUtilTest extends TestCase
{
    public function test_uuid(): void
    {
        $result = UuidUtil::uuid();

        $this->assertIsString($result);
        $this->assertEquals(36, strlen($result)); // Standard UUID format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $result);
    }

    public function test_uuid_generates_unique_values(): void
    {
        $uuid1 = UuidUtil::uuid();
        $uuid2 = UuidUtil::uuid();

        $this->assertNotEquals($uuid1, $uuid2);
    }

    public function test_shortUuid(): void
    {
        $result = UuidUtil::shortUuid();

        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result)); // UUID without dashes
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/i', $result);
    }

    public function test_shortUuid_generates_unique_values(): void
    {
        $uuid1 = UuidUtil::shortUuid();
        $uuid2 = UuidUtil::shortUuid();

        $this->assertNotEquals($uuid1, $uuid2);
    }

    public function test_expand(): void
    {
        $shortUuid = '123456781234123412341234567890ab';
        $result = UuidUtil::expand($shortUuid);

        $this->assertEquals('12345678-1234-1234-1234-1234567890ab', $result);
    }

    public function test_expand_with_already_expanded_uuid(): void
    {
        $expandedUuid = '12345678-1234-1234-1234-1234567890ab';
        $shortUuid = str_replace('-', '', $expandedUuid);
        $result = UuidUtil::expand($shortUuid);

        $this->assertEquals($expandedUuid, $result);
    }

    public function test_short(): void
    {
        $uuid = '12345678-1234-1234-1234-1234567890ab';
        $result = UuidUtil::short($uuid);

        $this->assertEquals('123456781234123412341234567890ab', $result);
    }

    public function test_short_with_already_short_uuid(): void
    {
        $shortUuid = '123456781234123412341234567890ab';
        $result = UuidUtil::short($shortUuid);

        $this->assertEquals($shortUuid, $result);
    }

    public function test_short_expand_roundtrip(): void
    {
        $original = '12345678-1234-1234-1234-1234567890ab';
        $short = UuidUtil::short($original);
        $expanded = UuidUtil::expand($short);

        $this->assertEquals($original, $expanded);
    }

    public function test_shortUuid_expand_roundtrip(): void
    {
        $short = UuidUtil::shortUuid();
        $expanded = UuidUtil::expand($short);
        $shortAgain = UuidUtil::short($expanded);

        $this->assertEquals($short, $shortAgain);
    }
}

