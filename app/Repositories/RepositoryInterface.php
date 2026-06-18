<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repository Interface for CRUD operations.
 */
interface RepositoryInterface
{
    /**
     * Get all records.
     */
    public function all(): Collection;

    /**
     * Find a record by ID.
     */
    public function find(string|int $id): ?Model;

    /**
     * Create a new record.
     */
    public function create(array $data): Model;

    /**
     * Update a record.
     */
    public function update(string|int $id, array $data): bool;

    /**
     * Delete a record.
     */
    public function delete(string|int $id): bool;

    /**
     * Paginate records.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Filter records with pagination.
     */
    public function filter(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Check if a record exists.
     */
    public function exists(string|int $id): bool;

    /**
     * Get query builder instance.
     */
    public function query();
}