<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMarketplaceData;
use Tests\TestCase;

class VendorProfileTest extends TestCase
{
    use RefreshDatabase, CreatesMarketplaceData;

    /** @return array{0: User, 1: Vendor} */
    private function actingVendor(): array
    {
        $owner = User::factory()->create(['role' => 'vendor']);
        $vendor = $this->makeVendor($owner);
        Sanctum::actingAs($owner);

        return [$owner, $vendor];
    }

    public function test_vendor_can_update_profile(): void
    {
        [, $vendor] = $this->actingVendor();

        $this->patchJson('/api/v1/vendor/profile', ['name' => 'New Name', 'city' => 'Bandung'])
            ->assertStatus(200);

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'name' => 'New Name', 'city' => 'Bandung']);
    }

    public function test_schedules_upsert_then_read_back_publicly(): void
    {
        [, $vendor] = $this->actingVendor();

        $schedules = [];
        foreach (range(0, 6) as $day) {
            $schedules[] = [
                'day_of_week' => $day,
                'open_time' => '08:00:00',
                'close_time' => '17:00:00',
                'is_closed' => $day === 0,
            ];
        }

        $this->patchJson('/api/v1/vendor/schedules', ['schedules' => $schedules])->assertStatus(200);
        $this->assertDatabaseCount('schedules', 7);

        // Public vendor detail must expose the saved schedules (regression: this was missing).
        $res = $this->getJson("/api/v1/vendors/{$vendor->slug}")->assertStatus(200);
        $this->assertCount(7, $res->json('data.schedules'));
    }

    public function test_schedule_validation_rejects_invalid_day(): void
    {
        $this->actingVendor();

        $this->patchJson('/api/v1/vendor/schedules', [
            'schedules' => [
                ['day_of_week' => 9, 'open_time' => '08:00:00', 'close_time' => '17:00:00', 'is_closed' => false],
            ],
        ])->assertStatus(422);
    }
}
