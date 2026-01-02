<?php

namespace App\Constants;

class ResponseConstant
{
    public const RET_OK = 0;

    public const RET_ERR = 1;

    public const RET_ERR_UNSUPPORTED_OPERATION  = 1000;

    public const RET_ERR_HTTP                   = 2000;

    public const RET_ERR_PARAM                  = 3000;

    public const RET_ERR_COMMAND                = 6000;

    public const RET_ERR_BIZZ_TASK              = 8100;
    public const RET_ERR_BIZZ_CRAWL             = 8200;
    public const RET_ERR_BIZZ_PLAN              = 8600;
}
