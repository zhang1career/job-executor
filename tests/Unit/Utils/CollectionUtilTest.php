<?php

namespace Tests\Unit\Utils;

use App\Exceptions\IllegalArgumentException;
use App\Utils\CollectionUtil;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class CollectionUtilTest extends TestCase
{
    public function test_columnOf_with_array_items(): void
    {
        $collection = new Collection([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ]);
        $result = CollectionUtil::columnOf($collection, 'id');

        $this->assertEquals([1, 2], $result);
    }

    public function test_columnOf_with_object_items(): void
    {
        $obj1 = (object)['id' => 1, 'name' => 'John'];
        $obj2 = (object)['id' => 2, 'name' => 'Jane'];
        $collection = new Collection([$obj1, $obj2]);
        $result = CollectionUtil::columnOf($collection, 'id');

        $this->assertEquals([1, 2], $result);
    }

    public function test_columnOf_throws_exception_for_blank_field(): void
    {
        $this->expectException(IllegalArgumentException::class);
        $collection = new Collection([['id' => 1]]);
        CollectionUtil::columnOf($collection, '');
    }

    public function test_columnOf_throws_exception_for_unsupported_type(): void
    {
        $this->expectException(IllegalArgumentException::class);
        $collection = new Collection([123]);
        CollectionUtil::columnOf($collection, 'id');
    }

    public function test_indexBy(): void
    {
        $obj1 = (object)['id' => 1, 'name' => 'John'];
        $obj2 = (object)['id' => 2, 'name' => 'Jane'];
        $collection = new Collection([$obj1, $obj2]);
        $result = CollectionUtil::indexBy($collection, 'id');

        $this->assertEquals(1, $result[1]->id);
        $this->assertEquals(2, $result[2]->id);
        $this->assertEquals('John', $result[1]->name);
        $this->assertEquals('Jane', $result[2]->name);
    }

    public function test_indexBy_throws_exception_for_blank_field(): void
    {
        $this->expectException(IllegalArgumentException::class);
        $obj = (object)['id' => 1];
        $collection = new Collection([$obj]);
        CollectionUtil::indexBy($collection, '');
    }

    public function test_indexBy_with_duplicate_keys(): void
    {
        $obj1 = (object)['id' => 1, 'name' => 'John'];
        $obj2 = (object)['id' => 1, 'name' => 'Jane'];
        $collection = new Collection([$obj1, $obj2]);
        $result = CollectionUtil::indexBy($collection, 'id');

        // The latter one should overwrite the former one
        $this->assertEquals('Jane', $result[1]->name);
    }
}

