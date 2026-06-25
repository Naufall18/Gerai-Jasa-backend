<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by the 'role:vendor' route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:150',
            'description' => 'nullable|string|max:2000',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
        ];
    }
}
