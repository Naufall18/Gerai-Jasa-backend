<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by the 'role:vendor' route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'max_advance_days' => 'nullable|integer|min:1|max:365',
        ];
    }
}
