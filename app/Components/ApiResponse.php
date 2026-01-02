<?php

namespace App\Components;

class ApiResponse
{

    /**
     * @param $data
     * @return array
     */
    public static function ok($data = null, $msg = '') : array {
        return [
            'data' => $data ?? '',
            'code'  => 0,
            'msg'  => $msg
        ];
    }

    /**
     * @param $code
     * @param $msg
     * @return array
     */
    public static function error($code, $msg) : array {
        return [
            'data' => '',
            'code'  => $code,
            'msg'  => $msg
        ];
    }
}
