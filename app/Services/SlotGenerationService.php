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

        for ($d = 0; $d < $days; $d++) {
            $date = $startDate->copy()->addDays($d);
            $dayOfWeek = $date->dayOfWeek;

            $schedule = $vendor->schedules->firstWhere('day_of_week', $dayOfWeek);
            if (!$schedule || $schedule->is_closed) {
                continue;
            }

            $openTime = Carbon::parse($schedule->open_time);
            $closeTime = Carbon::parse($schedule->close_time);

            // Default 30-min slot duration
            $slotDuration = 30;
            $cursor = $openTime->copy();

            while ($cursor->copy()->addMinutes($slotDuration)->lte($closeTime)) {
                $slot = TimeSlot::firstOrCreate(
                    [
                        'vendor_id' => $vendor->id,
                        'slot_date' => $date->toDateString(),
                        'slot_time' => $cursor->format('H:i:s'),
                    ],
                    [
                        'service_id' => null,
                        'capacity' => 1,
                        'booked_count' => 0,
                        'is_available' => true,
                    ]
                );

                if ($slot->wasRecentlyCreated) {
                    $created++;
                }

                $cursor->addMinutes($slotDuration);
            }
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