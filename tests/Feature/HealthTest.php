<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_probe_reports_ready(): void
    {
        $this->getJson('/api/v1/health/ready')
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.checks.database', 'ok');
    }

    public function test_response_carries_request_id_header(): void
    {
        $this->getJson('/api/v1/health/ready')
            ->assertHeader('X-Request-Id');
    }
}
