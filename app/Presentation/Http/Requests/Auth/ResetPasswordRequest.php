<?php

namespace App\Presentation\Http\Requests\Auth;

use App\Application\Identity\DTOs\ResetPasswordData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:filter'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    public function toData(): ResetPasswordData
    {
        return ResetPasswordData::fromArray($this->validated());
    }
}
