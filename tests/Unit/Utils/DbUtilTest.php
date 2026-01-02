<?php

namespace Tests\Unit\Utils;

use App\Models\User;
use App\Utils\DbUtil;
use Tests\TestCase;

class DbUtilTest extends TestCase
{
    public function test_getTable(): void
    {
        $tableName = DbUtil::getTable(User::class);

        $this->assertEquals('users', $tableName);
    }
}

