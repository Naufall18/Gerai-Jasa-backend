<?php

namespace Tests\Concerns;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Str;

trait CreatesMarketplaceData
{
    protected function makeVendor(?User $owner = null): Vendor
    {
        $owner ??= User::factory()->create(['role' => 'vendor']);
        $category = Category::forceCreate([
            'name' => 'Salon',
            'slug' => 'cat-' . Str::random(8),
            'is_active' => true,
        ]);

        return Vendor::forceCreate([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Test Vendor',
            'slug' => 'vendor-' . Str::random(8),
            'status' => 'active',
            'commission_rate' => 10,
        ]);
    }

    protected function makeService(Vendor $vendor): Service
    {
        return Service::forceCreate([
            'vendor_id' => $vendor->id,
            'name' => 'Haircut',
            'price' => 100000,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
    }

    protected function makeSlot(Vendor $vendor, ?Service $service = null, int $capacity = 1): TimeSlot
    {
        return TimeSlot::forceCreate([
            'vendor_id' => $vendor->id,
            'service_id' => $service?->id,
            'slot_date' => now()->addDay()->toDateString(),
            'slot_time' => '10:00:00',
            'capacity' => $capacity,
            'booked_count' => 0,
            'is_available' => true,
        ]);
    }

    protected function makeBooking(User $customer, Vendor $vendor, Service $service, TimeSlot $slot, string $status = 'pending', string $method = 'midtrans'): Booking
    {
        return Booking::forceCreate([
            'booking_code' => 'BKL-' . strtoupper(Str::random(8)),
            'customer_id' => $customer->id,
            'vendor_id' => $vendor->id,
            'service_id' => $service->id,
            'time_slot_id' => $slot->id,
            'status' => $status,
            'total_price' => 100000,
            'commission_amount' => 0,
            'payment_method' => $method,
        ]);
    }

    protected function makePayment(Booking $booking, string $status = 'pending'): Payment
    {
        return Payment::forceCreate([
            'booking_id' => $booking->id,
            'gateway' => 'midtrans',
            'amount' => 100000,
            'status' => $status,
            'gateway_ref' => $booking->booking_code,
        ]);
    }
}
