<?php

namespace App\Presentation\Http\Requests\Catalog;

use App\Application\Catalog\DTOs\ProductPriceData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductPriceRequest extends FormRequest
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
        return [
            'currency_id' => ['required', 'integer', 'min:1'],
            'price' => MoneyRules::required(),
        ];
    }

    public function toData(): ProductPriceData
    {
        return ProductPriceData::fromArray($this->validated());
    }
}
