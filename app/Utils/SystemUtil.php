<?php

namespace App\Utils;

use App\Constants\SystemConstant;
use Paganini\Exceptions\UnsupportedOperationException;
use Paganini\Utils\UuidUtil;

class SystemUtil
{

    /**
     * Get system UUID
     *
     * @return string
     * @throws UnsupportedOperationException
     */
    public static function getUuid(): string
    {
        $os = config('system.os');
        if ($os == SystemConstant::OS_EC2) {
            $uuid = exec('sudo dmidecode --string system-uuid');
            $uuid = UuidUtil::short($uuid);
            return $uuid;
        }
        throw new UnsupportedOperationException('System not supported, os=' . $os);
    }
}
