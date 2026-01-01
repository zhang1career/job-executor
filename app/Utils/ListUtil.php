<?php

namespace App\Utils;


class ListUtil
{
    /**
     * Get the max value of an array list
     *
     * @param $arrayList
     * @param $fieldName
     * @return mixed
     */
    public static function getMaxValue($arrayList, $fieldName) : mixed {
        if (!$arrayList) {
            return null;
        }

        $maxValue = null;
        foreach ($arrayList as $array) {
            if (!array_key_exists($fieldName, $array)) {
                continue;
            }
            if ($maxValue === null || $array[$fieldName] > $maxValue) {
                $maxValue = $array[$fieldName];
            }
        }
        return $maxValue;
    }

    /**
     * Get the min value of an array list
     *
     * @param $arrayList
     * @param $fieldName
     * @return mixed
     */
    public static function getMinValue($arrayList, $fieldName) : mixed {
        if (!$arrayList) {
            return null;
        }

        $minValue = null;
        foreach ($arrayList as $array) {
            if (!array_key_exists($fieldName, $array)) {
                continue;
            }
            if ($minValue === null || $array[$fieldName] < $minValue) {
                $minValue = $array[$fieldName];
            }
        }
        return $minValue;
    }
}
