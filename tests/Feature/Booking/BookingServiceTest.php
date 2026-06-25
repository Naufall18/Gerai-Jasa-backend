<?php

namespace Tests\Feature\Booking;

use App\Models\Category;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\Vendor;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeSlot(int $capacity = 1): array
    {
        $owner = User::factory()->create(['role' => 'vendor']);
        $customer = User::factory()->create(['role' => 'customer']);
        $category = Category::forceCreate(['name' => 'Salon', 'slug' => 'cat-' . Str::random(6), 'is_active' => true]);
        $vendor = Vendor::forceCreate([
            'user_id' => $owner->id, 'category_id' => $category->id,
            'name' => 'V', 'slug' => 'v-' . Str::random(6),
            'status' => 'active', 'commission_rate' => 10,
        ]);
        $service = Service::forceCreate([
            'vendor_id' => $vendor->id, 'name' => 'Cut', 'price' => 100000,
            'duration_minutes' => 30, 'is_active' => true,
        ]);
        $slot = TimeSlot::forceCreate([
            'vendor_id' => $vendor->id, 'service_id' => $service->id,
            'slot_date' => now()->addDay()->toDateString(), 'slot_time' => '10:00:00',
            'capacity' => $capacity, 'booked_count' => 0, 'is_available' => true,
        ]);

        return compact('customer', 'vendor', 'service', 'slot');
    }

    public function test_creating_booking_reserves_the_slot(): void
    {
        ['customer' => $c, 'vendor' => $v, 'service' => $s, 'slot' => $slot] = $this->makeSlot();

        $booking = app(BookingService::class)->createBooking($c->id, [
            'vendor_id' => $v->id, 'service_id' => $s->id, 'time_slot_id' => $slot->id,
            'total_price' => 100000, 'payment_method' => 'cod',
        ]);

        $this->assertSame('pending', $booking->status);
        $this->assertSame('BKL-', substr($booking->booking_code, 0, 4));
        // capacity 1 → slot now full and unavailable.
        $this->assertDatabaseHas('time_slots', ['id' => $slot->id, 'booked_count' => 1, 'is_available' => false]);
        // commission = 10% of 100000.
        $this->assertEquals(10000, (float) $booking->commission_amount);
    }

    public function test_double_booking_a_full_slot_is_rejected(): void
    {
        ['customer' => $c, 'vendor' => $v, 'service' => $s, 'slot' => $slot] = $this->makeSlot(1);
        $svc = app(BookingService::class);

        $svc->createBooking($c->id, [
            'vendor_id' => $v->id, 'service_id' => $s->id, 'time_slot_id' => $slot->id,
            'total_price' => 100000, 'payment_method' => 'cod',
        ]);

        $this->expectException(\Exception::class);
        $svc->createBooking($c->id, [
            'vendor_id' => $v->id, 'service_id' => $s->id, 'time_slot_id' => $slot->id,
            'total_price' => 100000, 'payment_method' => 'cod',
        ]);
    }

    public function test_cancelling_releases_the_slot(): void
    {
        ['customer' => $c, 'vendor' => $v, 'service' => $s, 'slot' => $slot] = $this->makeSlot();
        $svc = app(BookingService::class);

        $booking = $svc->createBooking($c->id, [
            'vendor_id' => $v->id, 'service_id' => $s->id, 'time_slot_id' => $slot->id,
            'total_price' => 100000, 'payment_method' => 'cod',
        ]);

        $svc->cancelBooking($booking->id, $c->id, 'changed my mind');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('time_slots', ['id' => $slot->id, 'booked_count' => 0, 'is_available' => true]);
    }

    public function test_confirm_from_payment_is_idempotent(): void
    {
        ['customer' => $c, 'vendor' => $v, 'service' => $s, 'slot' => $slot] = $this->makeSlot();
        $svc = app(BookingService::class);

        $booking = $svc->createBooking($c->id, [
            'vendor_id' => $v->id, 'service_id' => $s->id, 'time_slot_id' => $slot->id,
            'total_price' => 100000, 'payment_method' => 'midtrans',
        ]);

        $svc->confirmFromPayment($booking->id);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);

        // Replaying the webhook must not change or error.
        $svc->confirmFromPayment($booking->id);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }
}
