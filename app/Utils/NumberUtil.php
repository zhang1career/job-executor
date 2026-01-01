<?php

namespace App\Utils;

use App\Constants\NumberConstant;

class NumberUtil
{
    /**
     * Convert float by multipling 1e3, to fixed point number.
     * 0.123456 -> 123
     *
     * @param mixed $value
     * @return int
     */
    public static function fixed3FromFloat(mixed $value) : int {
        return intval(bcmul($value, NumberConstant::E_3, NumberConstant::SCALE_0));
    }

    /**
     * Convert float by multipling 1e6, to fixed point number.
     * 0.123456 -> 123456
     *
     * @param mixed $value
     * @return int
     */
    public static function fixed6FromFloat(mixed $value) : int {
        return intval(bcmul($value, NumberConstant::E_6, NumberConstant::SCALE_0));
    }

    /**
     * Convert fixed point number by dividing 1e6, to float with 6 digits after the decimal point.
     * 123456 -> 0.123456
     * @param mixed $value
     * @return string
     */
    public static function float6FromFixed6(mixed $value) : string {
        return bcdiv($value, NumberConstant::E_6, NumberConstant::SCALE_6);
    }
}
