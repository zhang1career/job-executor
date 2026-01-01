<?php

namespace App\Utils;

use Illuminate\Support\Str;

class UuidUtil
{

    /**
     * Generate a UUID
     *
     * @return string
     */
    public static function uuid() : string {
        return Str::uuid()->toString();
    }


    /**
     * Generate a short UUID
     *
     * @return array|string
     */
    public static function shortUuid() : array|string {
        return self::short(self::uuid());
    }


    /**
     * Expend a shorted UUID to normal format
     * '123456781234123412341234567890ab' -> '12345678-1234-1234-1234-1234567890ab'
     *
     * @param string $uuid
     * @return string
     */
    public static function expand(string $uuid) : string {
        return substr($uuid, 0, 8)
            . '-' . substr($uuid, 8, 4)
            . '-' . substr($uuid, 12, 4)
            . '-' . substr($uuid, 16, 4)
            . '-' . substr($uuid, 20);
    }


    /**
     * Shorten a UUID to a string without '-'
     * '12345678-1234-1234-1234-1234567890ab' -> '123456781234123412341234567890ab'
     *
     * @param string $uuid
     * @return array
     */
    public static function short(string $uuid) : string {
        return str_replace('-', '', $uuid);
    }
}
