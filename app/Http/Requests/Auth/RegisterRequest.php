<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => ['required', 'string', 'regex:/^(\+62|0)[0-9]{9,12}$/', 'unique:users,phone'],
            'password' => 'nullable|string|min:8|confirmed',
            // SECURITY: never allow self-registration as admin via the public endpoint.
            // Admin accounts are provisioned via seeder/console only.
            'role' => 'sometimes|in:vendor,customer',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must be a valid Indonesian number',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
        ];
    }
}