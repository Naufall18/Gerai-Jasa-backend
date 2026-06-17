<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;

class BookingRepository implements BookingRepositoryInterface
{
    public function find(string $id): ?Booking
    {
        return Booking::with(['vendor', 'service', 'timeSlot', 'payment'])->find($id);
    }

    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    public function update(string $id, array $data): Booking
    {
        $booking = Booking::findOrFail($id);
        $booking->update($data);
        return $booking->fresh();
    }

    public function listForCustomer(string $customerId, int $perPage = 20)
    {
        return Booking::with(['vendor', 'service', 'timeSlot', 'payment'])
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function listForVendor(string $vendorId, int $perPage = 20)
    {
        return Booking::with(['customer', 'service', 'timeSlot', 'payment'])
            ->where('vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}