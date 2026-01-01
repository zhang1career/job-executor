<?php

namespace App\Utils;

class DbUtil
{
    /**
     * get table name of given model class
     *
     * @param $modelClass
     * @return string
     */
    public static function getTable($modelClass) : string {
        return app($modelClass)->getTable();
    }
}
