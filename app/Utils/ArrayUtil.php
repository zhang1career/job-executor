<?php

namespace App\Utils;

use App\Exceptions\IllegalArgumentException;
use App\Exceptions\InvalidException;

class ArrayUtil
{
    /**
     * Get the excluding sub-arrays of two original arrays
     *
     * [1,2,3] and [2,3,4] => [1] and [4]
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function extersect(array $array1, array $array2) : array {
        $intersect = array_intersect($array1, $array2);
        $diff1     = array_diff($array1, $intersect);
        $diff2     = array_diff($array2, $intersect);
        return [$diff1, $diff2];
    }


    /**
     * Combine two arrays by key
     * if key exists in both arrays, combine the values with unique
     * with value sorted
     *
     * [
     *     'a' => [1, 2, 3],
     *     'b' => 2,
     *     'c' => [4, 5]
     * ]
     * +
     * [
     *     'a' => [3, 4],
     *     'b' => [2, 3],
     *     'd' => 6
     * ]
     * =
     * [
     *     'a' => [1, 2, 3, 4],
     *     'b' => [2, 3],
     *     'c' => [4, 5],
     *     'd' => [6]
     * ]
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function combineAndSort(array $array1, array $array2) : array {
        // Initialize the new combined array
        $combinedArray = [];

        // Get all unique keys from both arrays
        $keyList = array_unique(array_merge(array_keys($array1), array_keys($array2)));

        foreach ($keyList as $_key) {
            if (isset($array1[$_key]) && isset($array2[$_key])) {
                // Key exists in both arrays
                $combinedArray[$_key] = array_unique(array_merge((array)$array1[$_key], (array)$array2[$_key]));
            } elseif (isset($array1[$_key])) {
                // Key exists only in array1
                $combinedArray[$_key] = (array)$array1[$_key];
                continue;
            } else {
                // Key exists only in array2
                $combinedArray[$_key] = (array)$array2[$_key];
            }
            sort($combinedArray[$_key]);
        }

        return $combinedArray;
    }


    /**
     * Get the unique and sorted array
     *
     * @param array $array
     * @return array
     */
    public static function uniqAndSort(array $array) : array {
        $array = array_unique($array);
        sort($array);
        return $array;
    }


    /**
     * Split an array to some small arrays
     *
     * @param mixed $newsList
     * @param int $length
     * @return array
     */
    public static function partition(array $newsList, int $length) : array {
        return array_chunk($newsList, $length);
    }


    /**
     * Cartesian combine two arrays
     * [a, b] + [1, 2] -> [[a, 1], [a, 2], [b, 1], [b, 2]]
     *
     * @param array $array1
     * @param array $array2
     * @param callable $reducer
     * @return array
     */
    public static function cartesianCombine(array $array1, array $array2, callable $reducer) : array {
        if (!$array1 || !$array2) {
            return [];
        }

        $ret = [];
        foreach ($array1 as $_item1) {
            foreach ($array2 as $_item2) {
                $ret[] = $reducer($_item1, $_item2);
            }
        }
        return $ret;
    }


    /**
     * Get a specified column of an array
     *
     * @param array $array
     * @param string $fieldName
     * @return array
     * @throws IllegalArgumentException
     */
    public static function columnOf(array $array, string $fieldName) : array {
        if (StringUtil::isBlank($fieldName)) {
            throw new IllegalArgumentException('field should not be blank');
        }
        if (!$array) {
            return [];
        }
        return array_map(function ($_item) use ($fieldName) {
            if (is_array($_item) && isset($_item[$fieldName])) {
                return $_item[$fieldName];
            }
            if (is_object($_item) && isset($_item->$fieldName)) {
                return $_item->$fieldName;
            }
            throw new IllegalArgumentException('only array or object supported');
        }, $array);
    }


    /**
     * Index an array by a specified field
     * If some value conflicts, the latter one will overwrite the former one
     *
     * @param array $array
     * @param string $fieldName
     * @return array
     * @throws IllegalArgumentException
     */
    public static function indexBy(array $array, string $fieldName) : array {
        if (StringUtil::isBlank($fieldName)) {
            throw new IllegalArgumentException('field should not be blank');
        }
        if (!$array) {
            return [];
        }

        $ret = [];
        foreach ($array as $_item) {
            if (is_array($_item) && isset($_item[$fieldName])) {
                $_fieldValue       = $_item[$fieldName];
                $ret[$_fieldValue] = $_item;
                continue;
            }
            if (is_object($_item) && isset($_item->$fieldName)) {
                $_fieldValue       = $_item->$fieldName;
                $ret[$_fieldValue] = $_item;
                continue;
            }
            throw new IllegalArgumentException('only array or object supported');
        }

        return $ret;
    }


    /**
     * Group an array by a specified field
     * string, int, and enum is supported
     *
     * @param array $array
     * @param string $fieldName
     * @return array
     * @throws IllegalArgumentException
     */
    public static function groupBy(array $array, string $fieldName) : array {
        if (StringUtil::isBlank($fieldName)) {
            throw new IllegalArgumentException('field should not be blank');
        }
        if (!$array) {
            return [];
        }

        $ret = [];
        foreach ($array as $_item) {
            if (!isset($_item[$fieldName])) {
                continue;
            }
            $_key = $_item[$fieldName];

            if (!is_string($_key) && !is_int($_key)) {
                if (enum_exists($_key::class)) {
                    $_key = $_key->value;
                } else {
                    throw new IllegalArgumentException('only string, int or enum supported');
                }
            }

            if (!isset($ret[$_key])) {
                $ret[$_key] = [];
            }
            $ret[$_key][] = $_item;
        }

        return $ret;
    }


    /**
     * Get the values of an array by keys
     * ['a' => 'A1', 'b' => 'B2', 'c' => 'C3'] + ['a', 'c'] -> ['a' => 'A1', 'c' => 'C3']
     *
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function includeByKey(array $array, array $keys) : array {
        if (!$array || !$keys) {
            return [];
        }
        $keys = array_flip($keys);

        return array_intersect_key($array, $keys);
    }


    /**
     * Get the opposite values of an array by keys
     * ['a' => 'A1', 'b' => 'B2', 'c' => 'C3'] + ['a', 'c'] -> ['b' => 'B2']
     *
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function excludeByKey(array $array, array $keys) : array {
        if (!$array) {
            return [];
        }
        if (!$keys) {
            return $array;
        }
        $keys = array_flip($keys);

        return array_diff_key($array, $keys);
    }


    /**
     * Deeply get a value of an array
     * ['a' => ['b' => ['c' => 'value']]] + 'a.b.c' -> 'value'
     *
     * @param array $data
     * @param string $pathStr
     * @return array|mixed
     * @throws InvalidException
     */
    public static function deepGet(array $data, string $pathStr) : mixed {
        $paths = explode('.', $pathStr);
        $ret   = $data;
        foreach ($paths as $_path) {
            if (!$ret) {
                throw new InvalidException('data is null, path=' . $_path);
            }
            if (!isset($ret[$_path])) {
                throw new InvalidException('path not found, path=' . $_path);
            }
            $ret = $ret[$_path];
        }
        return $ret;
    }


    /**
     * Key Conflict Tolerance Insert
     * If the inserting key has not been stored before, simply insert the key-value pair.
     * If the inserting key exists in the array, compact these values as a list.
     * (k1, v1) + (k1, v2) + (k2, v3) -> ['k1' => [v1, v2], 'k2' => v3]
     *
     * @param $array
     * @param $key
     * @param $value
     * @return void
     */
    public static function conflictSafeInsert(&$array, $key, $value) {
        if (!array_key_exists($key, $array)) {
            $array[$key] = $value;
            return;
        }

        if (!is_array($array[$key])) {
            $array[$key] = [$array[$key]];
        }
        $array[$key][] = $value;
    }
}
