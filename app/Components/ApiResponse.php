<?php

namespace App\Components;

class ApiResponse
{

    /**
     * @param $data
     * @return array
     */
    public static function ok($data = null) : array {
        return [
            'data' => $data ?? '',
            'err'  => 0,
            'msg'  => ''
        ];
    }

    /**
     * @param $errorCode
     * @param $errorMessage
     * @return array
     */
    public static function error($errorCode, $errorMessage) : array {
        return [
            'data' => '',
            'err'  => $errorCode,
            'msg'  => $errorMessage
        ];
    }
}
