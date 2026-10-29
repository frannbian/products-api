<?php

namespace App\Presentation\Http\Requests\Catalog;

/**
 * Validation for amounts stored as decimal(12, 2).
 */
final class MoneyRules
{
    /**
     * @return list<string>
     */
    public static function required(): array
    {
        return ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'];
    }
}
