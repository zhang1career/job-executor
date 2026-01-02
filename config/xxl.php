<?php

use App\Jobs\ServiceManagement;

return [
    "admin_address" => env('XXL_JOB_ADMIN_ADDRESS'),
    "local_ip" => env('XXL_JOB_LOCAL_IP', 'http://locolhost'),
    "token" => env('XXL_JOB_TOKEN', 'default_token'),
    'jobs' => [
        "serviceDiscover" => [ServiceManagement::class, "discover"]
    ]
];
