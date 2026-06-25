<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMarketplaceData;
use Tests\TestCase;

class VendorServiceTest extends TestCase
{
    use RefreshDatabase, CreatesMarketplaceData;

    private function actingVendor(): User
    {
        $owner = User::factory()->create(['role' => 'vendor']);
        $this->makeVendor($owner);
        Sanctum::actingAs($owner);

        return $owner;
    }

    public function test_create_service_validates_required_fields(): void
    {
        $this->actingVendor();
        $this->postJson('/api/v1/vendor/services', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'duration_minutes']);
    }

    public function test_create_service_succeeds(): void
    {
        $this->actingVendor();
        $this->postJson('/api/v1/vendor/services', [
            'name' => 'Hair Color',
            'price' => 250000,
            'duration_minutes' => 90,
        ])->assertStatus(201);

        $this->assertDatabaseHas('services', ['name' => 'Hair Color', 'price' => 250000]);
    }

    public function test_vendor_cannot_update_another_vendors_service(): void
    {
        // Vendor A creates a service.
        $this->actingVendor();
        $serviceId = $this->postJson('/api/v1/vendor/services', [
            'name' => 'Owned', 'price' => 1000, 'duration_minutes' => 30,
        ])->json('data.id');

        // Vendor B may not touch it.
        $this->actingVendor();
        $this->patchJson("/api/v1/vendor/services/{$serviceId}", ['price' => 1])
            ->assertStatus(404);
    }
}
