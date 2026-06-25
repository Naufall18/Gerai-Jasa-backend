<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_self_register_as_admin(): void
    {
        $res = $this->postJson('/api/v1/auth/register', [
            'name' => 'Hacker',
            'email' => 'hacker@test.com',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $res->assertStatus(422)->assertJsonValidationErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'hacker@test.com']);
    }

    public function test_registration_defaults_to_customer(): void
    {
        $res = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi',
            'email' => 'budi@test.com',
            'phone' => '081234567891',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'budi@test.com', 'role' => 'customer']);
    }

    public function test_can_register_as_vendor(): void
    {
        $res = $this->postJson('/api/v1/auth/register', [
            'name' => 'Vendor Co',
            'email' => 'vendor@test.com',
            'phone' => '081234567892',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'vendor',
        ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'vendor@test.com', 'role' => 'vendor']);
    }
}
