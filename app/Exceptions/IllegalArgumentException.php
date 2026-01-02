<?php

namespace App\Exceptions;

use App\Constants\ResponseConstant;

class IllegalArgumentException extends BaseException
{
    protected static int $respCode = ResponseConstant::RET_ERR_PARAM;
}
