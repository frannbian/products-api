<?php

namespace App\Domain\Catalog\ValueObjects;

use App\Domain\Catalog\Exceptions\InvalidProduct;
use App\Domain\Shared\Uuid;

final readonly class ImportId
{
    public function __construct(
        private string $value
    ) {
        if (! preg_match(Uuid::PATTERN, $this->value)) {
            throw new InvalidProduct(
                'The import id must be a UUID.'
            );
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::v4());
    }

    public function value(): string
    {
        return $this->value;
    }
}
