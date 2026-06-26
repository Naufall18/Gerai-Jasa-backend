<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
        ];
    }
}
