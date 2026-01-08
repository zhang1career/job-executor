<?php

namespace App\Components;


class XxlResponse
{
    /**
     * @param null $data
     * @param string $msg
     * @return array
     */
    public static function success($data = null, string $msg = ""): array
    {
        return [
            'data' => $data,
            'code' => 200,
            'msg' => $msg
        ];
    }

    /**
     * @param string $message
     * @return array
     */
    public static function fail(string $message): array
    {
        return [
            'data' => null,
            'code' => 500,
            'msg' => $message
        ];
    }
}
