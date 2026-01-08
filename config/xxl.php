<?php

return [
    'admin_address' => 'http://xxl-job:8080/xxl-job-admin',
    'local_ip' => 'http://nginx:8199/api/xxl-job',
    'token' => '03cdd84a834e4542ae65227294e5278f',

    /**
     * Jobs configuration
     *
     * Recommended approach: Use XxlJob Attribute to mark business methods, system will automatically scan and register
     * Example:
     *   #[XxlJob('discoverService')]
     *   public static function discover(): array { ... }
     *
     * The following configuration is only for backward compatibility or manual registration in special cases
     * If using Attribute, this configuration can be removed
     */
    'jobs' => [
    ]
];
