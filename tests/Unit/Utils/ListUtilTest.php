<?php

namespace Tests\Unit\Utils;

use App\Utils\ListUtil;
use Tests\TestCase;

class ListUtilTest extends TestCase
{
    public function test_getMaxValue(): void
    {
        $arrayList = [
            ['id' => 1, 'value' => 10],
            ['id' => 2, 'value' => 30],
            ['id' => 3, 'value' => 20],
        ];
        $result = ListUtil::getMaxValue($arrayList, 'value');

        $this->assertEquals(30, $result);
    }

    public function test_getMaxValue_with_empty_list(): void
    {
        $result = ListUtil::getMaxValue([], 'value');

        $this->assertNull($result);
    }

    public function test_getMaxValue_with_missing_field(): void
    {
        $arrayList = [
            ['id' => 1, 'value' => 10],
            ['id' => 2], // missing value field
            ['id' => 3, 'value' => 20],
        ];
        $result = ListUtil::getMaxValue($arrayList, 'value');

        $this->assertEquals(20, $result);
    }

    public function test_getMaxValue_with_negative_numbers(): void
    {
        $arrayList = [
            ['id' => 1, 'value' => -10],
            ['id' => 2, 'value' => -5],
            ['id' => 3, 'value' => -20],
        ];
        $result = ListUtil::getMaxValue($arrayList, 'value');

        $this->assertEquals(-5, $result);
    }

    public function test_getMaxValue_with_single_item(): void
    {
        $arrayList = [
            ['id' => 1, 'value' => 10],
        ];
        $result = ListUtil::getMaxValue($arrayList, 'value');

        $this->assertEquals(10, $result);
    }

    public function test_getMinValue(): void
    {
        $arrayList = [
            ['id' => 1, 'value' => 10],
            ['id' => 2, 'value' => 30],
            ['id' => 3, 'value' => 20],
        ];
        $result = ListUtil::getMinValue($arrayList, 'value');

        $this->assertEquals(10, $result);
    }

    public function test_getMinValue_with_empty_list(): void
    {
        $result = ListUtil::getMinValue([], 'value');

        $this->assertNull($result);
    }

    public function test_getMinValue_with_missing_field(): void
    {
        $arrayList = [
            ['id' => 1, 'value' => 10],
            ['id' => 2], // missing value field
            ['id' => 3, 'value' => 20],
        ];
        $result = ListUtil::getMinValue($arrayList, 'value');

        $this->assertEquals(10, $result);
    }

    public function test_getMinValue_with_negative_numbers(): void
    {
        $arrayList = [
            ['id' => 1, 'value' => -10],
            ['id' => 2, 'value' => -5],
            ['id' => 3, 'value' => -20],
        ];
        $result = ListUtil::getMinValue($arrayList, 'value');

        $this->assertEquals(-20, $result);
    }

    public function test_getMinValue_with_single_item(): void
    {
        $arrayList = [
            ['id' => 1, 'value' => 10],
        ];
        $result = ListUtil::getMinValue($arrayList, 'value');

        $this->assertEquals(10, $result);
    }
}

