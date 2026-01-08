<?php

namespace App\Attributes;

use Attribute;

/**
 * XxlJob Attribute
 *
 * 用于标记 XXL-JOB 业务方法
 *
 * @example
 * #[XxlJob('discoverService')]
 * public static function discover(): array
 * {
 *     // 业务逻辑
 *     return [true, $result, null];
 * }
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class XxlJob
{
    /**
     * @param string $handler 任务标识，对应 XXL-JOB 中的 executorHandler
     */
    public function __construct(
        public string $handler
    )
    {
    }
}
