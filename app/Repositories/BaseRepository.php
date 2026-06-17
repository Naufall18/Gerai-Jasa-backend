<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository class for common CRUD operations.
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    /**
     * Set the model instance.
     *
     * @return Model
     */
    abstract public function model(): Model;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->model = $this->model();
    }

    /**
     * Get all records.
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Find a record by ID.
     *
     * @param string|int $id
     * @return Model|null
     */
    public function find(string|int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Create a new record.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record.
     *
     * @param string|int $id
     * @param array $data
     * @return bool
     */
    public function update(string|int $id, array $data): bool
    {
        $model = $this->find($id);
        if (!$model) {
            return false;
        }
        return $model->update($data);
    }

    /**
     * Delete a record.
     *
     * @param string|int $id
     * @return bool
     */
    public function delete(string|int $id): bool
    {
        $model = $this->find($id);
        if (!$model) {
            return false;
        }
        return $model->delete();
    }

    /**
     * Paginate records.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Get records with filters and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function filter(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        foreach ($filters as $key => $value) {
            if ($value !== null) {
                $query->where($key, $value);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Check if a record exists.
     *
     * @param string|int $id
     * @return bool
     */
    public function exists(string|int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Get query builder instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return $this->model->query();
    }
}