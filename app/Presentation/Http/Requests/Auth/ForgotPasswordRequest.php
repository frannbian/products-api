<?php

namespace App\Presentation\Http\Requests\Auth;

use App\Domain\Identity\ValueObjects\Email;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
            'email' => ['required', 'string', 'email:filter'],
        ];
    }

    public function email(): Email
    {
        return new Email($this->validated('email'));
    }
}
