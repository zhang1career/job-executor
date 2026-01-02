<?php

namespace App\Exceptions;

use App\Constants\ResponseConstant;
use Exception;

abstract class BaseException extends Exception
{
    /**
     * @var int $respCode response code
     */
    protected static int $respCode = ResponseConstant::RET_ERR;

    public function getRespCode() : int {
        return self::$respCode;
    }
}
