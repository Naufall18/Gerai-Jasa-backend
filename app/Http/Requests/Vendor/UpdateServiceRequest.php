<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by the 'role:vendor' route middleware
        // (ownership is enforced in the controller).
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'price' => 'sometimes|numeric|min:0',
            'duration_minutes' => 'sometimes|integer|min:15|max:480',
            'max_advance_days' => 'nullable|integer|min:1|max:365',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
