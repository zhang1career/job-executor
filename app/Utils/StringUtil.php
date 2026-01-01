<?php

namespace App\Utils;

use App\Exceptions\IllegalArgumentException;

class StringUtil
{

    /**
     * @param $str
     * @return bool
     */
    public static function isBlank($str) : bool {
        if (!$str) {
            return true;
        }
        $trimmedParam = trim($str);
        return $trimmedParam === '';
    }


    /**
     * @param $str
     * @return bool
     */
    public static function isNotBlank($str) : bool {
        return !self::isBlank($str);
    }


    /**
     * @throws IllegalArgumentException
     */
    public static function checkBlank(string $str) : string {
        $str = trim($str);
        if($str === '') {
            throw new IllegalArgumentException('argument should not be blank');
        }
        return $str;
    }


    /**
     * Truncate a string, replace by tail if the string is too long
     *
     * @param string $str
     * @param int $len
     * @param string $tail
     * @return string
     */
    public static function truncate(string $str, int $len, string $tail = '...') : string {
        return (strlen($str) > $len) ? substr($str, 0, $len - strlen($tail)) . $tail : $str;
    }
}
