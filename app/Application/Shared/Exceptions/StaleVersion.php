<?php

namespace App\Application\Shared\Exceptions;

use RuntimeException;

/**
 * The client asked to modify a version that is no longer the current one.
 */
final class StaleVersion extends RuntimeException
{
    public function __construct(
        public readonly int $expected,
        public readonly int $actual,
    ) {
        parent::__construct("Expected version {$expected}, but the current version is {$actual}.");
    }

    public static function check(?int $expected, int $actual): void
    {
        if ($expected !== null && $expected !== $actual) {
            throw new self($expected, $actual);
        }
    }
}
