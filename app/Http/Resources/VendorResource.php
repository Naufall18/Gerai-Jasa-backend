<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Vendor $resource
 */
class VendorResource extends JsonResource
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
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'status' => $this->status,
            'commission_rate' => $this->commission_rate,
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'meta' => $this->meta,
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'photos' => VendorPhotoResource::collection($this->whenLoaded('photos')),
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
        ];
    }
}