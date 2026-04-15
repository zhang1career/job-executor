<?php

return [
    'admin_address' => rtrim(
        env('XXL_JOB_ADMIN_ADDRESS', 'http://xxl-job:8080/xxl-job-admin'),
        '/'
    ),
    'local_ip' => env('XXL_JOB_LOCAL_IP', 'http://nginx:8199/api/xxl-job'),
    'token' => env('XXL_JOB_TOKEN', '03cdd84a834e4542ae65227294e5278f'),
];
