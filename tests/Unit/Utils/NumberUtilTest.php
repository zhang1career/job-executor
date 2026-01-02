<?php

namespace Tests\Unit\Utils;

use App\Utils\NumberUtil;
use Tests\TestCase;

class NumberUtilTest extends TestCase
{
    public function test_fixed3FromFloat(): void
    {
        $result = NumberUtil::fixed3FromFloat(0.123456);
        $this->assertEquals(123, $result);
    }

    public function test_fixed3FromFloat_with_zero(): void
    {
        $result = NumberUtil::fixed3FromFloat(0.0);
        $this->assertEquals(0, $result);
    }

    public function test_fixed3FromFloat_with_negative(): void
    {
        $result = NumberUtil::fixed3FromFloat(-0.123456);
        $this->assertEquals(-123, $result);
    }

    public function test_fixed3FromFloat_with_large_number(): void
    {
        $result = NumberUtil::fixed3FromFloat(123.456);
        $this->assertEquals(123456, $result);
    }

    public function test_fixed6FromFloat(): void
    {
        $result = NumberUtil::fixed6FromFloat(0.123456);
        $this->assertEquals(123456, $result);
    }

    public function test_fixed6FromFloat_with_zero(): void
    {
        $result = NumberUtil::fixed6FromFloat(0.0);
        $this->assertEquals(0, $result);
    }

    public function test_fixed6FromFloat_with_negative(): void
    {
        $result = NumberUtil::fixed6FromFloat(-0.123456);
        $this->assertEquals(-123456, $result);
    }

    public function test_fixed6FromFloat_with_large_number(): void
    {
        $result = NumberUtil::fixed6FromFloat(123.456789);
        $this->assertEquals(123456789, $result);
    }

    public function test_float6FromFixed6(): void
    {
        $result = NumberUtil::float6FromFixed6(123456);
        $this->assertEquals('0.123456', $result);
    }

    public function test_float6FromFixed6_with_zero(): void
    {
        $result = NumberUtil::float6FromFixed6(0);
        $this->assertEquals('0.000000', $result);
    }

    public function test_float6FromFixed6_with_negative(): void
    {
        $result = NumberUtil::float6FromFixed6(-123456);
        $this->assertEquals('-0.123456', $result);
    }

    public function test_float6FromFixed6_with_large_number(): void
    {
        $result = NumberUtil::float6FromFixed6(123456789);
        $this->assertEquals('123.456789', $result);
    }

    public function test_fixed6FromFloat_float6FromFixed6_roundtrip(): void
    {
        $original = 0.123456;
        $fixed = NumberUtil::fixed6FromFloat($original);
        $float = NumberUtil::float6FromFixed6($fixed);

        $this->assertEquals('0.123456', $float);
    }
}

