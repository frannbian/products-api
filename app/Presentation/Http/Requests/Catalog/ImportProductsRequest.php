<?php

namespace App\Presentation\Http\Requests\Catalog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Every row is validated with the same rules as a single product before the
 * import is accepted: errors point to the row (products.3.price). Only
 * business rules that need the database are checked while processing.
 */
class ImportProductsRequest extends FormRequest
{
    public const MAX_ROWS = 1000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rows = [];

        foreach (ProductRequest::fieldRules() as $field => $rules) {
            $rows["products.*.{$field}"] = $rules;
        }

        return [
            'products' => ['required', 'array', 'list', 'min:1', 'max:'.self::MAX_ROWS],
            'products.*' => ['required', 'array'],
            ...$rows,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->validated('products');
    }
}
