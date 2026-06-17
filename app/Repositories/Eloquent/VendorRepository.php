<?php

namespace App\Repositories\Eloquent;

use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;

class VendorRepository implements VendorRepositoryInterface
{
    public function find(string $id): ?Vendor
    {
        return Vendor::with(['category', 'services', 'photos'])->find($id);
    }

    public function findBySlug(string $slug): ?Vendor
    {
        return Vendor::with(['category', 'services', 'photos'])->where('slug', $slug)->first();
    }

    public function upsert(array $data): Vendor
    {
        return Vendor::updateOrCreate(
            ['id' => $data['id'] ?? null],
            $data
        );
    }

    public function list(array $filters = [], int $perPage = 20)
    {
        $query = Vendor::query()->with(['category', 'services', 'photos']);

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (isset($filters['city'])) {
            $query->where('city', 'ilike', '%' . $filters['city'] . '%');
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['rating'])) {
            $query->where('rating_avg', '>=', $filters['rating']);
        }
        if (isset($filters['search'])) {
            $q = $filters['search'];
            $query->where(function($builder) use ($q) {
                $builder->where('name', 'ilike', "%$q%")
                    ->orWhere('slug', 'ilike', "%$q%")
                    ->orWhere('description', 'ilike', "%$q%");
            });
        }

        return $query->orderByDesc('is_featured')
                     ->orderBy('name')
                     ->paginate($perPage);
    }
}