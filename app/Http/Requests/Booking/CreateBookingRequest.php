<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // must be authenticated (sanctum)
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'uuid'],
            'service_id' => ['nullable', 'uuid'],
            'time_slot_id' => ['required', 'uuid'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cod,midtrans,xendit'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('total_price')) {
            $this->merge([
                'total_price' => (float) $this->input('total_price'),
            ]);
        }
    }
}