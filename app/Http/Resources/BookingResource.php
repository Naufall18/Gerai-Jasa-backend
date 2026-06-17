<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Booking $resource
 */
class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'status' => $this->status,
            'notes' => $this->notes,
            'special_requests' => $this->special_requests,
            'total_price' => $this->total_price,
            'commission_amount' => $this->commission_amount,
            'payment_method' => $this->payment_method,
            'confirmed_at' => $this->confirmed_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'time_slot' => new TimeSlotResource($this->whenLoaded('timeSlot')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'customer' => new UserResource($this->whenLoaded('customer')),
        ];
    }
}