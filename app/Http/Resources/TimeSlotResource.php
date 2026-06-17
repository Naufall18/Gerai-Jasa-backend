<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\TimeSlot $resource
 */
class TimeSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'service_id' => $this->service_id,
            'slot_date' => $this->slot_date,
            'slot_time' => $this->slot_time,
            'capacity' => $this->capacity,
            'booked_count' => $this->booked_count,
            'is_available' => $this->is_available,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}