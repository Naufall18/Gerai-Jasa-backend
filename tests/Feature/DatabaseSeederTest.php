<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test migrations and seeding process.
     */
    public function test_database_migrations_and_seeder_run_successfully(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@geraijasa.id',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('categories', [
            'slug' => 'salon',
        ]);

        $this->assertDatabaseCount('vendors', 3);
        $this->assertDatabaseCount('services', 10);
        $this->assertDatabaseCount('schedules', 21); // 3 vendors * 7 days
    }
}