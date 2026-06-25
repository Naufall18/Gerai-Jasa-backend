<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Schedule $resource
 */
class ScheduleResource extends JsonResource
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
            'day_of_week' => $this->day_of_week,
            'open_time' => $this->open_time,
            'close_time' => $this->close_time,
            'is_closed' => $this->is_closed,
        ];
    }
}
