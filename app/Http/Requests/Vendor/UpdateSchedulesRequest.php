<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchedulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by the 'role:vendor' route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'schedules' => 'required|array|min:1|max:7',
            'schedules.*.day_of_week' => 'required|integer|between:0,6',
            'schedules.*.open_time' => 'required|date_format:H:i:s',
            'schedules.*.close_time' => 'required|date_format:H:i:s',
            'schedules.*.is_closed' => 'required|boolean',
        ];
    }
}
