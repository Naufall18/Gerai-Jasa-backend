<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;

interface BookingRepositoryInterface
{
    /**
     * Find booking by id.
     *
     * @param string $id
     * @return Booking|null
     */
    public function find(string $id): ?Booking;

    /**
     * Create new booking.
     *
     * @param array $data
     * @return Booking
     */
    public function create(array $data): Booking;

    /**
     * Update an existing booking.
     *
     * @param string $id
     * @param array $data
     * @return Booking
     */
    public function update(string $id, array $data): Booking;

    /**
     * List bookings for customer.
     *
     * @param string $customerId
     * @param int $perPage
     * @return mixed
     */
    public function listForCustomer(string $customerId, int $perPage = 20);

    /**
     * List bookings for vendor.
     *
     * @param string $vendorId
     * @param int $perPage
     * @return mixed
     */
    public function listForVendor(string $vendorId, int $perPage = 20);
}