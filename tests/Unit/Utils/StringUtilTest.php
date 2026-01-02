<?php

namespace Tests\Unit\Utils;

use App\Exceptions\IllegalArgumentException;
use App\Utils\StringUtil;
use Tests\TestCase;

class StringUtilTest extends TestCase
{
    public function test_isBlank_with_null(): void
    {
        $this->assertTrue(StringUtil::isBlank(null));
    }

    public function test_isBlank_with_empty_string(): void
    {
        $this->assertTrue(StringUtil::isBlank(''));
    }

    public function test_isBlank_with_whitespace(): void
    {
        $this->assertTrue(StringUtil::isBlank('   '));
        $this->assertTrue(StringUtil::isBlank("\t\n\r"));
    }

    public function test_isBlank_with_non_blank_string(): void
    {
        $this->assertFalse(StringUtil::isBlank('hello'));
        $this->assertFalse(StringUtil::isBlank('  hello  '));
    }

    public function test_isNotBlank(): void
    {
        $this->assertFalse(StringUtil::isNotBlank(null));
        $this->assertFalse(StringUtil::isNotBlank(''));
        $this->assertFalse(StringUtil::isNotBlank('   '));
        $this->assertTrue(StringUtil::isNotBlank('hello'));
    }

    public function test_checkBlank_with_valid_string(): void
    {
        $result = StringUtil::checkBlank('hello');
        $this->assertEquals('hello', $result);
    }

    public function test_checkBlank_trims_string(): void
    {
        $result = StringUtil::checkBlank('  hello  ');
        $this->assertEquals('hello', $result);
    }

    public function test_checkBlank_throws_exception_for_empty_string(): void
    {
        $this->expectException(IllegalArgumentException::class);
        StringUtil::checkBlank('');
    }

    public function test_checkBlank_throws_exception_for_whitespace(): void
    {
        $this->expectException(IllegalArgumentException::class);
        StringUtil::checkBlank('   ');
    }

    public function test_truncate_with_short_string(): void
    {
        $result = StringUtil::truncate('hello', 10);
        $this->assertEquals('hello', $result);
    }

    public function test_truncate_with_long_string(): void
    {
        $result = StringUtil::truncate('hello world', 8);
        $this->assertEquals('hello...', $result);
    }

    public function test_truncate_with_custom_tail(): void
    {
        $result = StringUtil::truncate('hello world', 8, '***');
        $this->assertEquals('hello***', $result);
    }

    public function test_truncate_with_exact_length(): void
    {
        $result = StringUtil::truncate('hello', 5);
        $this->assertEquals('hello', $result);
    }

    public function test_truncate_with_length_shorter_than_tail(): void
    {
        $result = StringUtil::truncate('hello world', 3, '...');
        // When length is shorter than tail, it will still truncate
        $this->assertEquals('...', $result);
    }
}

