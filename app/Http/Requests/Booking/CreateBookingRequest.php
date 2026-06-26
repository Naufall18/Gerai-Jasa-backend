<?php

namespace App\Http\Requests\Booking;

use App\Models\Service;
use App\Models\TimeSlot;
use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'vendor_id'      => ['required', 'uuid', 'exists:vendors,id'],
            'service_id'     => ['nullable', 'uuid', 'exists:services,id'],
            'time_slot_id'   => ['required', 'uuid', 'exists:time_slots,id'],
            'payment_method' => ['required', 'in:cod,midtrans,xendit'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * After validation, inject the real price from the service record
     * so the client cannot manipulate total_price.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $serviceId = $this->input('service_id');

            if ($serviceId) {
                $service = Service::find($serviceId);

                if (!$service) {
                    $validator->errors()->add('service_id', 'Service not found.');
                    return;
                }

                if (!$service->is_active) {
                    $validator->errors()->add('service_id', 'This service is currently unavailable.');
                    return;
                }

                if ((string) $service->vendor_id !== (string) $this->input('vendor_id')) {
                    $validator->errors()->add('service_id', 'Service does not belong to this vendor.');
                    return;
                }

                // Override total_price with the real service price (cannot be manipulated by client)
                $this->merge(['total_price' => (float) $service->price]);
            } else {
                // No service selected — default to 0 (vendor will set price manually)
                $this->merge(['total_price' => 0.0]);
            }
        });
    }

    /**
     * Validated payload plus the server-derived `total_price`.
     *
     * `total_price` is injected in withValidator() and has no validation rule,
     * so it is intentionally absent from validated() — expose it explicitly here
     * so the controller/service receive the price the client cannot tamper with.
     */
    public function bookingData(): array
    {
        return array_merge($this->validated(), [
            'total_price' => (float) $this->input('total_price', 0),
        ]);
    }
}
