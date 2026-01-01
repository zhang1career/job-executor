<?php

namespace App\Utils;

class CodecUtil
{
    /**
     * Encode array to string
     * comma seperated
     *
     * @param array $array
     * @return string
     */
    public static function comma_encode(array $array) : string {
        return implode(',', $array);;
    }

    /**
     * Decode a string to array
     * comma seperated
     *
     * @param string $str
     * @return string[]
     */
    public static function comma_decode(string $str) : array {
        return explode(',', $str);
    }
}
