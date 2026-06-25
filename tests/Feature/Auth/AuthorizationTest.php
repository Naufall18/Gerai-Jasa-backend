<?php

namespace Tests\Feature\Auth;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function actAs(string $role, bool $withVendorProfile = false): User
    {
        $user = User::factory()->create(['role' => $role]);

        if ($withVendorProfile) {
            $category = Category::forceCreate([
                'name' => 'Salon',
                'slug' => 'salon-' . Str::random(6),
                'is_active' => true,
            ]);
            Vendor::forceCreate([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'name' => 'Test Vendor',
                'slug' => 'vendor-' . Str::random(6),
                'status' => 'active',
            ]);
        }

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $this->actAs('admin');
        $this->getJson('/api/v1/admin/users')->assertStatus(200);
    }

    public function test_customer_cannot_access_admin_routes(): void
    {
        $this->actAs('customer');
        $this->getJson('/api/v1/admin/users')->assertStatus(403);
    }

    public function test_vendor_cannot_access_admin_routes(): void
    {
        $this->actAs('vendor', withVendorProfile: true);
        $this->getJson('/api/v1/admin/users')->assertStatus(403);
    }

    public function test_vendor_can_access_vendor_routes(): void
    {
        $this->actAs('vendor', withVendorProfile: true);
        $this->getJson('/api/v1/vendor/services')->assertStatus(200);
    }

    public function test_customer_cannot_access_vendor_routes(): void
    {
        $this->actAs('customer');
        $this->getJson('/api/v1/vendor/services')->assertStatus(403);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/v1/admin/users')->assertStatus(401);
    }
}
