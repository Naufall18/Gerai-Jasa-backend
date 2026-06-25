<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\TimeSlot;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    /**
     * Get available slots for a vendor/service/date combo.
     * Cached in Redis for 60 seconds to reduce DB load.
     *
     * @param string $vendorId
     * @param string|null $serviceId
     * @param string $date  (Y-m-d)
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableSlots(string $vendorId, ?string $serviceId, string $date)
    {
        // Cache per vendor+date only. A single slot can appear in results for
        // multiple service filters (service-specific OR vendor-wide null-service),
        // so keying the cache by service_id made invalidation incorrect/stale.
        $cacheKey = "slots:{$vendorId}:{$date}";

        $slots = Cache::remember($cacheKey, 60, function () use ($vendorId, $date) {
            return TimeSlot::where('vendor_id', $vendorId)
                ->where('slot_date', $date)
                ->where('is_available', true)
                ->whereRaw('booked_count < capacity')
                ->orderBy('slot_time')
                ->get();
        });

        // Filter by service in memory so the cache entry stays valid for any filter.
        if ($serviceId) {
            $slots = $slots->filter(
                fn ($slot) => $slot->service_id === $serviceId || $slot->service_id === null
            )->values();
        }

        return $slots;
    }

    /**
     * Create a new booking and associated payment record.
     * Uses DB transaction + pessimistic lock on time slot.
     *
     * @param string $customerId
     * @param array $data
     * @return Booking
     *
     * @throws \Exception
     */
    public function createBooking(string $customerId, array $data): Booking
    {
        return DB::transaction(function () use ($customerId, $data) {
            // Pessimistic lock on the slot to prevent race condition
            $slot = TimeSlot::lockForUpdate()->findOrFail($data['time_slot_id']);

            if (!$slot->is_available || $slot->booked_count >= $slot->capacity) {
                throw new \Exception('Selected time slot is no longer available.');
            }

            $vendor = Vendor::findOrFail($data['vendor_id']);
            $commission = round($data['total_price'] * ($vendor->commission_rate / 100), 2);

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'customer_id' => $customerId,
                'vendor_id' => $data['vendor_id'],
                'service_id' => $data['service_id'],
                'time_slot_id' => $slot->id,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'special_requests' => $data['special_requests'] ?? null,
                'total_price' => $data['total_price'],
                'commission_amount' => $commission,
                'payment_method' => $data['payment_method'],
            ]);

            // Reserve the slot
            $slot->increment('booked_count');
            if ($slot->booked_count >= $slot->capacity) {
                $slot->update(['is_available' => false]);
            }

            // Create pending payment record
            Payment::create([
                'booking_id' => $booking->id,
                'gateway' => $data['payment_method'] === 'cod' ? 'cod' : $data['payment_method'],
                'amount' => $data['total_price'],
                'status' => 'pending',
            ]);

            // Invalidate slot cache (keyed by vendor+date)
            Cache::forget("slots:{$booking->vendor_id}:{$slot->slot_date}");

            return $booking->load(['vendor', 'service', 'timeSlot', 'payment']);
        });
    }

    /**
     * List customer bookings.
     *
     * @param string $customerId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listCustomerBookings(string $customerId, int $perPage = 20)
    {
        return Booking::with(['vendor', 'service', 'timeSlot', 'payment'])
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Find booking with customer access control.
     *
     * @param string $bookingId
     * @param string $customerId
     * @return Booking
     */
    public function findBooking(string $bookingId, string $customerId): Booking
    {
        return Booking::with(['vendor', 'service', 'timeSlot', 'payment', 'customer'])
            ->where('id', $bookingId)
            ->where('customer_id', $customerId)
            ->firstOrFail();
    }

    /**
     * List bookings for a vendor.
     *
     * @param string $vendorId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listVendorBookings(string $vendorId, int $perPage = 20)
    {
        return Booking::with(['customer', 'service', 'timeSlot', 'payment'])
            ->where('vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * List all bookings across the platform (admin view), with optional status filter.
     *
     * @param string|null $status
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listAllBookings(?string $status = null, int $perPage = 20)
    {
        return Booking::with(['customer', 'vendor', 'service', 'timeSlot', 'payment'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Confirm a booking (vendor action).
     *
     * @param string $bookingId
     * @param string $vendorId
     * @return Booking
     *
     * @throws \Exception
     */
    public function confirmBooking(string $bookingId, string $vendorId): Booking
    {
        return DB::transaction(function () use ($bookingId, $vendorId) {
            $booking = Booking::where('id', $bookingId)
                ->where('vendor_id', $vendorId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->firstOrFail();

            $booking->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            // Dispatch SendBookingConfirmationJob to notify customer
            \App\Jobs\SendBookingConfirmationJob::dispatch($booking);

            return $booking->fresh(['vendor', 'service', 'customer', 'timeSlot']);
        });
    }

    /**
     * Cancel a booking.
     *
     * @param string $bookingId
     * @param string $userId
     * @param string $reason
     * @return Booking
     *
     * @throws \Exception
     */
    public function cancelBooking(string $bookingId, string $userId, string $reason): Booking
    {
        return DB::transaction(function () use ($bookingId, $userId, $reason) {
            $booking = Booking::where('id', $bookingId)
                ->where(fn ($q) => $q->where('customer_id', $userId)->orWhereHas('vendor', fn ($q) => $q->where('user_id', $userId)))
                ->whereIn('status', ['pending', 'confirmed'])
                ->lockForUpdate()
                ->firstOrFail();

            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Release the slot
            $slot = $booking->timeSlot;
            if ($slot) {
                $lockedSlot = TimeSlot::lockForUpdate()->findOrFail($slot->id);
                $lockedSlot->decrement('booked_count');
                $lockedSlot->update(['is_available' => true]);

                Cache::forget("slots:{$booking->vendor_id}:{$lockedSlot->slot_date}");
            }

            // Trigger refund if already paid
            $payment = $booking->payment;
            if ($payment && $payment->status === 'paid') {
                $payment->update([
                    'status' => 'refunded',
                ]);
            }

            // Notify the customer of the cancellation.
            app(\App\Services\NotificationService::class)->sendBookingCancellation($booking);

            return $booking->fresh(['vendor', 'service', 'customer', 'timeSlot', 'payment']);
        });
    }

    /**
     * Complete a booking (vendor action).
     *
     * @param string $bookingId
     * @param string $vendorId
     * @return Booking
     *
     * @throws \Exception
     */
    public function completeBooking(string $bookingId, string $vendorId): Booking
    {
        return DB::transaction(function () use ($bookingId, $vendorId) {
            $booking = Booking::where('id', $bookingId)
                ->where('vendor_id', $vendorId)
                ->where('status', 'confirmed')
                ->lockForUpdate()
                ->firstOrFail();

            $booking->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // If COD, mark payment as paid upon service completion
            $payment = $booking->payment;
            if ($payment && $booking->payment_method === 'cod' && $payment->status !== 'paid') {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            return $booking->fresh(['vendor', 'service', 'customer', 'timeSlot', 'payment']);
        });
    }

    /**
     * Confirm a booking after a successful gateway payment (webhook-triggered).
     * Idempotent: acts only on a still-pending booking, and locks the row to avoid
     * racing with a concurrent vendor confirmation.
     *
     * @param string $bookingId
     * @return void
     */
    public function confirmFromPayment(string $bookingId): void
    {
        $booking = DB::transaction(function () use ($bookingId) {
            $booking = Booking::where('id', $bookingId)->lockForUpdate()->first();

            if (!$booking || $booking->status !== 'pending') {
                return null;
            }

            $booking->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            return $booking;
        });

        // Dispatch after commit so the queued job observes the persisted change.
        if ($booking) {
            \App\Jobs\SendBookingConfirmationJob::dispatch($booking);
        }
    }

    /**
     * Generate a unique booking code.
     *
     * @return string
     */
    private function generateBookingCode(): string
    {
        do {
            $code = 'BKL-' . strtoupper(Str::random(8));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}