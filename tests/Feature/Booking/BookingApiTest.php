<?php

namespace Tests\Feature\Booking;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMarketplaceData;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase, CreatesMarketplaceData;

    public function test_customer_can_create_cod_booking_via_http(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor = $this->makeVendor();
        $service = $this->makeService($vendor); // price 100000
        $slot = $this->makeSlot($vendor, $service);

        Sanctum::actingAs($customer);

        // Regression: total_price is injected server-side (withValidator) and must
        // reach the service even though it has no validation rule.
        $res = $this->postJson('/api/v1/bookings', [
            'vendor_id' => $vendor->id,
            'service_id' => $service->id,
            'time_slot_id' => $slot->id,
            'payment_method' => 'cod',
        ]);

        $res->assertStatus(200)->assertJsonPath('data.total_price', '100000.00');
        $this->assertDatabaseHas('bookings', [
            'customer_id' => $customer->id,
            'vendor_id' => $vendor->id,
            'status' => 'pending',
        ]);
    }

    public function test_booking_requires_time_slot_and_payment_method(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/bookings', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['vendor_id', 'time_slot_id', 'payment_method']);
    }
}
