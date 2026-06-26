<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMarketplaceData;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase, CreatesMarketplaceData;

    private function confirmedBookingFor(User $customer): void
    {
        $vendor = $this->makeVendor();
        $service = $this->makeService($vendor);
        $slot = $this->makeSlot($vendor, $service);
        $booking = $this->makeBooking($customer, $vendor, $service, $slot, 'pending');

        app(NotificationService::class)->sendBookingConfirmation($booking);
    }

    public function test_booking_event_records_and_lists_a_notification(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->confirmedBookingFor($customer);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $customer->id,
            'type' => 'booking_confirmed',
        ]);

        Sanctum::actingAs($customer);
        $res = $this->getJson('/api/v1/notifications')->assertStatus(200);

        $this->assertSame(1, $res->json('meta.unread_count'));
        $this->assertSame('booking_confirmed', $res->json('data.0.type'));
        $this->assertFalse($res->json('data.0.is_read'));
    }

    public function test_mark_notification_as_read(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->confirmedBookingFor($customer);

        Sanctum::actingAs($customer);
        $id = $this->getJson('/api/v1/notifications')->json('data.0.id');

        $this->patchJson("/api/v1/notifications/{$id}/read")->assertStatus(200);

        $this->assertSame(0, $this->getJson('/api/v1/notifications')->json('meta.unread_count'));
    }

    public function test_notifications_are_scoped_to_the_user(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $this->confirmedBookingFor($owner);

        Sanctum::actingAs($other);
        $res = $this->getJson('/api/v1/notifications')->assertStatus(200);
        $this->assertSame(0, $res->json('meta.unread_count'));
        $this->assertCount(0, $res->json('data'));
    }
}
