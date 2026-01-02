<?php

namespace Tests\Unit\Utils;

use App\Exceptions\IllegalArgumentException;
use App\Utils\ArrayUtil;
use Tests\TestCase;

class ArrayUtilTest extends TestCase
{
    public function test_extersect(): void
    {
        $array1 = [1, 2, 3];
        $array2 = [2, 3, 4];
        $result = ArrayUtil::extersect($array1, $array2);

        $this->assertEquals([1], array_values($result[0]));
        $this->assertEquals([4], array_values($result[1]));
    }

    public function test_extersect_with_empty_arrays(): void
    {
        $result = ArrayUtil::extersect([], []);
        $this->assertEquals([[], []], $result);
    }

    public function test_extersect_with_no_intersection(): void
    {
        $array1 = [1, 2, 3];
        $array2 = [4, 5, 6];
        $result = ArrayUtil::extersect($array1, $array2);

        $this->assertEquals([1, 2, 3], array_values($result[0]));
        $this->assertEquals([4, 5, 6], array_values($result[1]));
    }

    public function test_combineAndSort(): void
    {
        $array1 = [
            'a' => [1, 2, 3],
            'b' => 2,
            'c' => [4, 5]
        ];
        $array2 = [
            'a' => [3, 4],
            'b' => [2, 3],
            'd' => 6
        ];
        $result = ArrayUtil::combineAndSort($array1, $array2);

        $this->assertEquals([1, 2, 3, 4], $result['a']);
        $this->assertEquals([2, 3], $result['b']);
        $this->assertEquals([4, 5], $result['c']);
        $this->assertEquals([6], $result['d']);
    }

    public function test_combineAndSort_with_empty_arrays(): void
    {
        $result = ArrayUtil::combineAndSort([], []);
        $this->assertEquals([], $result);
    }

    public function test_uniqAndSort(): void
    {
        $array = [3, 1, 2, 2, 3, 1];
        $result = ArrayUtil::uniqAndSort($array);

        $this->assertEquals([1, 2, 3], $result);
    }

    public function test_uniqAndSort_with_empty_array(): void
    {
        $result = ArrayUtil::uniqAndSort([]);
        $this->assertEquals([], $result);
    }

    public function test_partition(): void
    {
        $array = [1, 2, 3, 4, 5, 6, 7];
        $result = ArrayUtil::partition($array, 3);

        $this->assertEquals([[1, 2, 3], [4, 5, 6], [7]], $result);
    }

    public function test_partition_with_exact_divisor(): void
    {
        $array = [1, 2, 3, 4, 5, 6];
        $result = ArrayUtil::partition($array, 3);

        $this->assertEquals([[1, 2, 3], [4, 5, 6]], $result);
    }

    public function test_cartesianCombine(): void
    {
        $array1 = ['a', 'b'];
        $array2 = [1, 2];
        $result = ArrayUtil::cartesianCombine($array1, $array2, function ($a, $b) {
            return [$a, $b];
        });

        $this->assertEquals([['a', 1], ['a', 2], ['b', 1], ['b', 2]], $result);
    }

    public function test_cartesianCombine_with_empty_array(): void
    {
        $result = ArrayUtil::cartesianCombine([], [1, 2], fn($a, $b) => [$a, $b]);
        $this->assertEquals([], $result);
    }

    public function test_columnOf_with_array(): void
    {
        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];
        $result = ArrayUtil::columnOf($array, 'id');

        $this->assertEquals([1, 2], $result);
    }

    public function test_columnOf_with_object(): void
    {
        $obj1 = (object)['id' => 1, 'name' => 'John'];
        $obj2 = (object)['id' => 2, 'name' => 'Jane'];
        $array = [$obj1, $obj2];
        $result = ArrayUtil::columnOf($array, 'id');

        $this->assertEquals([1, 2], $result);
    }

    public function test_columnOf_throws_exception_for_blank_field(): void
    {
        $this->expectException(IllegalArgumentException::class);
        ArrayUtil::columnOf([['id' => 1]], '');
    }

    public function test_columnOf_throws_exception_for_unsupported_type(): void
    {
        $this->expectException(IllegalArgumentException::class);
        ArrayUtil::columnOf([123], 'id');
    }

    public function test_columnOf_with_empty_array(): void
    {
        $result = ArrayUtil::columnOf([], 'id');
        $this->assertEquals([], $result);
    }

    public function test_indexBy_with_array(): void
    {
        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];
        $result = ArrayUtil::indexBy($array, 'id');

        $this->assertEquals(1, $result[1]['id']);
        $this->assertEquals(2, $result[2]['id']);
        $this->assertEquals('John', $result[1]['name']);
        $this->assertEquals('Jane', $result[2]['name']);
    }

    public function test_indexBy_with_object(): void
    {
        $obj1 = (object)['id' => 1, 'name' => 'John'];
        $obj2 = (object)['id' => 2, 'name' => 'Jane'];
        $array = [$obj1, $obj2];
        $result = ArrayUtil::indexBy($array, 'id');

        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals(2, $result[2]->id);
    }

    public function test_indexBy_throws_exception_for_blank_field(): void
    {
        $this->expectException(IllegalArgumentException::class);
        ArrayUtil::indexBy([['id' => 1]], '');
    }

    public function test_indexBy_with_empty_array(): void
    {
        $result = ArrayUtil::indexBy([], 'id');
        $this->assertEquals([], $result);
    }

    public function test_groupBy(): void
    {
        $array = [
            ['category' => 'A', 'value' => 1],
            ['category' => 'B', 'value' => 2],
            ['category' => 'A', 'value' => 3],
        ];
        $result = ArrayUtil::groupBy($array, 'category');

        $this->assertCount(2, $result['A']);
        $this->assertCount(1, $result['B']);
    }

    public function test_groupBy_with_int_key(): void
    {
        $array = [
            ['type' => 1, 'value' => 'a'],
            ['type' => 2, 'value' => 'b'],
            ['type' => 1, 'value' => 'c'],
        ];
        $result = ArrayUtil::groupBy($array, 'type');

        $this->assertCount(2, $result[1]);
        $this->assertCount(1, $result[2]);
    }

    public function test_groupBy_skips_items_without_field(): void
    {
        $array = [
            ['category' => 'A', 'value' => 1],
            ['value' => 2], // no category
            ['category' => 'A', 'value' => 3],
        ];
        $result = ArrayUtil::groupBy($array, 'category');

        $this->assertCount(2, $result['A']);
        $this->assertArrayNotHasKey('', $result);
    }

    public function test_groupBy_with_empty_array(): void
    {
        $result = ArrayUtil::groupBy([], 'category');
        $this->assertEquals([], $result);
    }

    public function test_groupBy_with_enum(): void
    {
        // Skip test for PHP < 8.1 as enums are not supported
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Enum support requires PHP 8.1+');
        }

        // Note: Enum test would require defining an enum outside the class
        // For now, we'll skip this test as it requires additional setup
        $this->markTestSkipped('Enum test requires enum definition outside test class');
    }

    public function test_includeByKey(): void
    {
        $array = ['a' => 'A1', 'b' => 'B2', 'c' => 'C3'];
        $keys = ['a', 'c'];
        $result = ArrayUtil::includeByKey($array, $keys);

        $this->assertEquals(['a' => 'A1', 'c' => 'C3'], $result);
    }

    public function test_includeByKey_with_empty_arrays(): void
    {
        $result = ArrayUtil::includeByKey([], []);
        $this->assertEquals([], $result);
    }

    public function test_excludeByKey(): void
    {
        $array = ['a' => 'A1', 'b' => 'B2', 'c' => 'C3'];
        $keys = ['a', 'c'];
        $result = ArrayUtil::excludeByKey($array, $keys);

        $this->assertEquals(['b' => 'B2'], $result);
    }

    public function test_excludeByKey_with_empty_keys(): void
    {
        $array = ['a' => 'A1', 'b' => 'B2'];
        $result = ArrayUtil::excludeByKey($array, []);

        $this->assertEquals($array, $result);
    }

    public function test_excludeByKey_with_empty_array(): void
    {
        $result = ArrayUtil::excludeByKey([], ['a']);
        $this->assertEquals([], $result);
    }

    public function test_deepGet(): void
    {
        $data = ['a' => ['b' => ['c' => 'value']]];
        $result = ArrayUtil::deepGet($data, 'a.b.c');

        $this->assertEquals('value', $result);
    }

    public function test_deepGet_throws_exception_for_null_path(): void
    {
        $this->expectException(IllegalArgumentException::class);
        $data = ['a' => null];
        ArrayUtil::deepGet($data, 'a.b');
    }

    public function test_deepGet_throws_exception_for_missing_path(): void
    {
        $this->expectException(IllegalArgumentException::class);
        $data = ['a' => ['b' => 'value']];
        ArrayUtil::deepGet($data, 'a.b.c');
    }

    public function test_conflictSafeInsert_new_key(): void
    {
        $array = [];
        ArrayUtil::conflictSafeInsert($array, 'key1', 'value1');

        $this->assertEquals('value1', $array['key1']);
    }

    public function test_conflictSafeInsert_existing_key(): void
    {
        $array = ['key1' => 'value1'];
        ArrayUtil::conflictSafeInsert($array, 'key1', 'value2');

        $this->assertEquals(['value1', 'value2'], $array['key1']);
    }

    public function test_conflictSafeInsert_multiple_values(): void
    {
        $array = [];
        ArrayUtil::conflictSafeInsert($array, 'key1', 'value1');
        ArrayUtil::conflictSafeInsert($array, 'key1', 'value2');
        ArrayUtil::conflictSafeInsert($array, 'key1', 'value3');

        $this->assertEquals(['value1', 'value2', 'value3'], $array['key1']);
    }
}

