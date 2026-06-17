<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\BaseRepository;

class UserRepository extends BaseRepository
{
    public function model(): User
    {
        return new User();
    }

    /**
     * Find user by email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find user by phone.
     *
     * @param string $phone
     * @return User|null
     */
    public function findByPhone(string $phone): ?User
    {
        return $this->model->where('phone', $phone)->first();
    }

    /**
     * Find active user by ID.
     *
     * @param string $id
     * @return User|null
     */
    public function findActive(string $id): ?User
    {
        return $this->model->where('id', $id)->where('is_active', true)->first();
    }
}