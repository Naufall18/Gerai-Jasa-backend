<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\TimeSlot;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SlotSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = Vendor::with(['schedules', 'services'])->get();

        foreach ($vendors as $vendor) {
            $this->generateSlotsForVendor($vendor, 14); // 14 days for seeder
        }
    }

    private function generateSlotsForVendor(Vendor $vendor, int $days): void
    {
        $startDate = Carbon::today();

        for ($d = 0; $d < $days; $d++) {
            $date = $startDate->copy()->addDays($d);
            $dayOfWeek = $date->dayOfWeek; // 0=Sun .. 6=Sat

            $schedule = $vendor->schedules->firstWhere('day_of_week', $dayOfWeek);
            if (!$schedule || $schedule->is_closed) {
                continue;
            }

            $openTime = Carbon::parse($schedule->open_time);
            $closeTime = Carbon::parse($schedule->close_time);

            // Generate 30-min slots by default
            $slotDuration = 30;
            $cursor = $openTime->copy();

            while ($cursor->copy()->addMinutes($slotDuration)->lte($closeTime)) {
                TimeSlot::updateOrCreate(
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

                $cursor->addMinutes($slotDuration);
            }
        }
    }
}