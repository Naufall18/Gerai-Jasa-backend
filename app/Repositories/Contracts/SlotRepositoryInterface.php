<?php

namespace App\Repositories\Contracts;

use App\Models\TimeSlot;

interface SlotRepositoryInterface
{
    /**
     * Find slot by id.
     *
     * @param string $id
     * @return TimeSlot|null
     */
    public function find(string $id): ?TimeSlot;

    /**
     * List available slots for a vendor + date (+service).
     *
     * @param string $vendorId
     * @param string|null $serviceId
     * @param string $date (Y-m-d)
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableSlots(string $vendorId, ?string $serviceId, string $date);
}