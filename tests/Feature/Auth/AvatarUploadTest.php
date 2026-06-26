<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'customer']);
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('me.jpg', 600, 600);

        $res = $this->postJson('/api/v1/auth/avatar', ['avatar' => $file]);

        $res->assertStatus(200);
        $this->assertNotNull($res->json('data.user.avatar_url'));
        $this->assertStringContainsString('/storage/avatars/', $res->json('data.user.avatar_url'));

        // A file was actually written to the public disk.
        $this->assertGreaterThan(0, count(Storage::disk('public')->files('avatars')));
    }

    public function test_avatar_rejects_non_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'customer']);
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf');

        $this->postJson('/api/v1/auth/avatar', ['avatar' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors('avatar');
    }

    public function test_avatar_requires_auth(): void
    {
        $this->postJson('/api/v1/auth/avatar', [])->assertStatus(401);
    }
}
