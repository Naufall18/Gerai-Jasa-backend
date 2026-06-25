<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMarketplaceData;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase, CreatesMarketplaceData;

    public function test_vendor_list_returns_standard_pagination_meta(): void
    {
        $this->makeVendor();

        $res = $this->getJson('/api/v1/vendors')->assertStatus(200);

        $pagination = $res->json('meta.pagination');
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('per_page', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('last_page', $pagination);
    }

    public function test_vendor_search_and_city_filter_do_not_error(): void
    {
        // Regression: 'ilike' crashed on MySQL; must be a clean 200 now.
        $this->makeVendor();

        $this->getJson('/api/v1/vendors?search=Test&city=Bandung')->assertStatus(200);
    }
}
