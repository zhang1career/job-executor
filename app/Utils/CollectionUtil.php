<?php

namespace App\Utils;

use Illuminate\Database\Eloquent\Collection;
use Paganini\Exceptions\IllegalArgumentException;
use Paganini\Utils\StringUtil;

class CollectionUtil
{
    /**
     * Get a specified column of a collection
     *
     * @param Collection $array
     * @param string $fieldName
     * @return array
     * @throws IllegalArgumentException
     */
    public static function columnOf(Collection $array, string $fieldName): array
    {
        if (StringUtil::isBlank($fieldName)) {
            throw new IllegalArgumentException('field should not be blank');
        }
        return array_map(function ($_item) use ($fieldName) {
            if (is_array($_item) && isset($_item[$fieldName])) {
                return $_item[$fieldName];
            }
            if (is_object($_item) && isset($_item->$fieldName)) {
                return $_item->$fieldName;
            }
            throw new IllegalArgumentException('only array or object supported');
        }, $array->toArray());
    }


    /**
     * Get a map of a collection, with a specified key
     *
     * @param Collection $array
     * @param string $fieldName
     * @return array
     * @throws IllegalArgumentException
     */
    public static function indexBy(Collection $array, string $fieldName): array
    {
        if (StringUtil::isBlank($fieldName)) {
            throw new IllegalArgumentException('field should not be blank');
        }

        $ret = [];
        foreach ($array as $_item) {
            $_fieldValue = $_item->$fieldName;
            $ret[$_fieldValue] = $_item;
        }

        return $ret;
    }
}
