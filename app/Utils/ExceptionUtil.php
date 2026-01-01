<?php

namespace App\Utils;

use Exception;
use Illuminate\Support\Facades\Log;

class ExceptionUtil
{

    /**
     * Log exception trace
     *
     * @param $foreword
     * @param Exception $e
     * @return void
     */
    public static function logTrace($foreword, Exception $e) : void {
        Log::error($foreword
                   . ', errcode=' . $e->getCode()
                   . ', errmsg=' . $e->getMessage()
                   . ', trace[0]=' . json_encode($e->getTrace()[0]));
    }
}
