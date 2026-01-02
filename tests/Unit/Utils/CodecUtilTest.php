<?php

namespace Tests\Unit\Utils;

use App\Utils\CodecUtil;
use Tests\TestCase;

class CodecUtilTest extends TestCase
{
    public function test_comma_encode(): void
    {
        $array = ['a', 'b', 'c'];
        $result = CodecUtil::comma_encode($array);

        $this->assertEquals('a,b,c', $result);
    }

    public function test_comma_encode_with_empty_array(): void
    {
        $result = CodecUtil::comma_encode([]);
        $this->assertEquals('', $result);
    }

    public function test_comma_encode_with_single_element(): void
    {
        $result = CodecUtil::comma_encode(['single']);
        $this->assertEquals('single', $result);
    }

    public function test_comma_encode_with_numbers(): void
    {
        $array = [1, 2, 3];
        $result = CodecUtil::comma_encode($array);

        $this->assertEquals('1,2,3', $result);
    }

    public function test_comma_decode(): void
    {
        $str = 'a,b,c';
        $result = CodecUtil::comma_decode($str);

        $this->assertEquals(['a', 'b', 'c'], $result);
    }

    public function test_comma_decode_with_empty_string(): void
    {
        $result = CodecUtil::comma_decode('');
        $this->assertEquals([''], $result);
    }

    public function test_comma_decode_with_single_element(): void
    {
        $result = CodecUtil::comma_decode('single');
        $this->assertEquals(['single'], $result);
    }

    public function test_comma_decode_with_numbers(): void
    {
        $result = CodecUtil::comma_decode('1,2,3');
        $this->assertEquals(['1', '2', '3'], $result);
    }

    public function test_comma_encode_decode_roundtrip(): void
    {
        $original = ['a', 'b', 'c', 'd'];
        $encoded = CodecUtil::comma_encode($original);
        $decoded = CodecUtil::comma_decode($encoded);

        $this->assertEquals($original, $decoded);
    }
}

