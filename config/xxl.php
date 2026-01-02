<?php

use App\Jobs\ServiceManagement;

return [
    "admin_address" => 'http://xxl-job:8080/xxl-job-admin/',
    "local_ip" => "http://nginx:8199/api/xxl-job",
    "token" => "default_token",
    'jobs' => [
        "serviceDiscover" => [ServiceManagement::class, "discover"]
    ]
];
