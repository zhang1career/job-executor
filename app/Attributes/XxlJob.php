<?php

namespace App\Attributes;

use Attribute;

/**
 * XxlJob Attribute
 *
 * Used to mark XXL-JOB business methods
 *
 * @example
 * #[XxlJob('discoverService')]
 * public static function discover(): array
 * {
 *     // Business logic
 *     return [true, $result, null];
 * }
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class XxlJob
{
    /**
     * @param string $handler Job identifier, corresponds to executorHandler in XXL-JOB
     */
    public function __construct(
        public string $handler
    )
    {
    }
}
