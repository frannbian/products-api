<?php

namespace App\Presentation\Http\Requests\Catalog;

use App\Application\Catalog\DTOs\ProductData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Used to create (POST) and fully replace (PUT) a product.
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::fieldRules();
    }

    /**
     * Rules of one product, shared with the bulk import.
     *
     * @return array<string, list<string>>
     */
    public static function fieldRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'price' => MoneyRules::required(),
            'currency_id' => ['required', 'integer', 'min:1'],
            'tax_cost' => MoneyRules::required(),
            'manufacturing_cost' => MoneyRules::required(),
        ];
    }

    public function toData(): ProductData
    {
        return ProductData::fromArray($this->validated());
    }
}
