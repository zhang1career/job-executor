<?php

return [
    'admin_address' => 'http://xxl-job:8080/xxl-job-admin',
    'local_ip' => 'http://nginx:8199/api/xxl-job',
    'token' => '03cdd84a834e4542ae65227294e5278f',

    /**
     * Jobs 配置
     *
     * 推荐方式：使用 XxlJob Attribute 标记业务方法，系统会自动扫描并注册
     * 示例：
     *   #[XxlJob('discoverService')]
     *   public static function discover(): array { ... }
     *
     * 以下配置仅用于向后兼容或特殊情况下的手动注册
     * 如果使用 Attribute，可以删除此配置
     */
    'jobs' => [
    ]
];
