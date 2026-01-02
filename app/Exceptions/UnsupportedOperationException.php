<?php

namespace App\Exceptions;

use App\Constants\ResponseConstant;

class UnsupportedOperationException extends BaseException
{
    protected static int $respCode = ResponseConstant::RET_ERR_UNSUPPORTED_OPERATION;
}
