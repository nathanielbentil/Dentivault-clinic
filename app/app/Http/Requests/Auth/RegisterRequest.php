<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * Enforces NIST SP 800-63B & OWASP password complexity recommendations.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(3), // Checks against HaveIBeenPwned database to block breached passwords
            ],
        ];
    }

    /**
     * Custom error messages for password policy education.
     */
    public function messages(): array
    {
        return [
            'password.min' => 'For bank-grade security, passwords must be at least 12 characters long.',
            'password.uncompromised' => 'The chosen password has appeared in a known global data breach. Please choose a unique password.',
        ];
    }
}
