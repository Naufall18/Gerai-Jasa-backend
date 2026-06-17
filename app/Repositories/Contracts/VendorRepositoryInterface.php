<?php

namespace App\Repositories\Contracts;

use App\Models\Vendor;

interface VendorRepositoryInterface
{
    /**
     * Find vendor by id.
     *
     * @param string $id
     * @return Vendor|null
     */
    public function find(string $id): ?Vendor;

    /**
     * Find vendor by slug.
     *
     * @param string $slug
     * @return Vendor|null
     */
    public function findBySlug(string $slug): ?Vendor;

    /**
     * Create or update a vendor.
     *
     * @param array $data
     * @return Vendor
     */
    public function upsert(array $data): Vendor;

    /**
     * List vendors, with optional filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return mixed
     */
    public function list(array $filters = [], int $perPage = 20);
}