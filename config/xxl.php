<?php

use App\Jobs\RuleBroker;
use App\Jobs\ServiceManagement;

return [
    'admin_address' => 'http://xxl-job:8080/xxl-job-admin/',
    'local_ip' => 'http://nginx:8199/api/xxl-job',
    'token' => '03cdd84a834e4542ae65227294e5278f',
    'jobs' => [
        'discoverService' => [ServiceManagement::class, 'discover'],

        'loginRuleBroker' => [RuleBroker::class, 'login'],
        'refreshRuleBroker' => [RuleBroker::class, 'refresh'],

    ]
];
