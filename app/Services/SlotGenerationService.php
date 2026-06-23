<?php

namespace App\Services;

use App\Models\TimeSlot;
use App\Models\Vendor;
use Carbon\Carbon;

class SlotGenerationService
{
    /**
     * Generate time slots for a vendor for the given number of days.
     *
     * @param Vendor $vendor
     * @param int $days
     * @return int Number of slots created
     */
    public function generateForVendor(Vendor $vendor, int $days = 60): int
    {
        $vendor->loadMissing(['schedules', 'services']);
        $startDate = Carbon::today();
        $created = 0;

        // Extract off-days/holidays from vendor metadata if present
        $holidays = $vendor->meta['holidays'] ?? []; // Array of dates in Y-m-d format
        $customBreaks = $vendor->meta['breaks'] ?? []; // Array of [start_time, end_time] (H:i:s format)

        for ($d = 0; $d < $days; $d++) {
            $date = $startDate->copy()->addDays($d);
            $dateStr = $date->toDateString();

            // Skip if the date is in vendor holidays list
            if (in_array($dateStr, $holidays)) {
                continue;
            }

            $dayOfWeek = $date->dayOfWeek;
            $schedule = $vendor->schedules->firstWhere('day_of_week', $dayOfWeek);

            // Skip if schedule not found or vendor is closed on this day of week
            if (!$schedule || $schedule->is_closed) {
                continue;
            }

            $openTime = Carbon::parse($schedule->open_time);
            $closeTime = Carbon::parse($schedule->close_time);

            // Generate slots for each active service of the vendor
            $activeServices = $vendor->services->where('is_active', true);

            if ($activeServices->isEmpty()) {
                // Default fallback if no active services: generate general 30-min slots
                $created += $this->generateSlotsForDuration(
                    $vendor->id,
                    $dateStr,
                    $openTime,
                    $closeTime,
                    30,
                    null,
                    $customBreaks
                );
            } else {
                foreach ($activeServices as $service) {
                    // Respect service max_advance_days
                    $maxAdvanceDays = $service->max_advance_days ?? 30;
                    if ($d > $maxAdvanceDays) {
                        continue;
                    }

                    $duration = $service->duration_minutes ?: 30;
                    $created += $this->generateSlotsForDuration(
                        $vendor->id,
                        $dateStr,
                        $openTime,
                        $closeTime,
                        $duration,
                        $service->id,
                        $customBreaks
                    );
                }
            }
        }

        return $created;
    }

    /**
     * Helper to generate slots for a specific duration, service, and handle custom breaks.
     */
    private function generateSlotsForDuration(
        string $vendorId,
        string $dateStr,
        Carbon $openTime,
        Carbon $closeTime,
        int $duration,
        ?string $serviceId,
        array $customBreaks
    ): int {
        $created = 0;
        $cursor = $openTime->copy();

        while ($cursor->copy()->addMinutes($duration)->lte($closeTime)) {
            $slotStart = $cursor->format('H:i:s');
            $slotEnd = $cursor->copy()->addMinutes($duration)->format('H:i:s');

            // Check if slot overlaps with any custom break times
            $isInsideBreak = false;
            foreach ($customBreaks as $break) {
                $breakStart = $break['start_time'] ?? null;
                $breakEnd = $break['end_time'] ?? null;

                if ($breakStart && $breakEnd) {
                    // Check overlap between [$slotStart, $slotEnd] and [$breakStart, $breakEnd]
                    if ($slotStart < $breakEnd && $slotEnd > $breakStart) {
                        $isInsideBreak = true;
                        break;
                    }
                }
            }

            if (!$isInsideBreak) {
                $slot = TimeSlot::firstOrCreate(
                    [
                        'vendor_id' => $vendorId,
                        'service_id' => $serviceId,
                        'slot_date' => $dateStr,
                        'slot_time' => $slotStart,
                    ],
                    [
                        'capacity' => 1,
                        'booked_count' => 0,
                        'is_available' => true,
                    ]
                );

                if ($slot->wasRecentlyCreated) {
                    $created++;
                }
            }

            $cursor->addMinutes($duration);
        }

        return $created;
    }

    /**
     * Generate slots for all active vendors.
     *
     * @param int $days
     * @return int Total slots created
     */
    public function generateForAllVendors(int $days = 60): int
    {
        $total = 0;
        Vendor::where('status', 'active')->chunk(50, function ($vendors) use ($days, &$total) {
            foreach ($vendors as $vendor) {
                $total += $this->generateForVendor($vendor, $days);
            }
        });

        return $total;
    }
}
