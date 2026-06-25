<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMarketplaceData;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase, CreatesMarketplaceData;

    public function test_cannot_review_an_uncompleted_booking(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor = $this->makeVendor();
        $service = $this->makeService($vendor);
        $slot = $this->makeSlot($vendor, $service);
        $booking = $this->makeBooking($customer, $vendor, $service, $slot, 'pending');

        Sanctum::actingAs($customer);
        $this->postJson("/api/v1/bookings/{$booking->id}/review", ['rating' => 5])
            ->assertStatus(404);
    }

    public function test_can_review_completed_booking_only_once(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor = $this->makeVendor();
        $service = $this->makeService($vendor);
        $slot = $this->makeSlot($vendor, $service);
        $booking = $this->makeBooking($customer, $vendor, $service, $slot, 'completed');

        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/bookings/{$booking->id}/review", ['rating' => 5, 'comment' => 'Great'])
            ->assertStatus(201);

        // Second review for the same booking is rejected.
        $this->postJson("/api/v1/bookings/{$booking->id}/review", ['rating' => 4])
            ->assertStatus(422);

        $this->assertDatabaseHas('reviews', ['booking_id' => $booking->id, 'rating' => 5]);
        // Vendor aggregate rating updated.
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'rating_count' => 1]);
    }
}
