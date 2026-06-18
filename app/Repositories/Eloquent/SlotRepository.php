<?php

namespace App\Repositories\Eloquent;

use App\Models\TimeSlot;
use App\Repositories\Contracts\SlotRepositoryInterface;

class SlotRepository implements SlotRepositoryInterface
{
    public function find(string $id): ?TimeSlot
    {
        return TimeSlot::find($id);
    }

    public function getAvailableSlots(string $vendorId, ?string $serviceId, string $date)
    {
        return TimeSlot::where('vendor_id', $vendorId)
            ->where('slot_date', $date)
            ->where('is_available', true)
            ->whereRaw('booked_count < capacity')
            ->when($serviceId, fn ($q) => $q->where(fn ($sub) => $sub->where('service_id', $serviceId)->orWhereNull('service_id')))
            ->orderBy('slot_time')
            ->get();
    }
}