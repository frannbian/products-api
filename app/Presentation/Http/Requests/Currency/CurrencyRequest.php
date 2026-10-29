<?php

namespace App\Presentation\Http\Requests\Currency;

use App\Application\Currency\DTOs\CurrencyData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Used to create (POST) and fully replace (PUT) a currency.
 */
class CurrencyRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:10'],
            'exchange_rate' => ['required', 'numeric', 'decimal:0,6', 'gt:0', 'max:999999999999.999999'],
        ];
    }

    public function toData(): CurrencyData
    {
        return CurrencyData::fromArray($this->validated());
    }
}
