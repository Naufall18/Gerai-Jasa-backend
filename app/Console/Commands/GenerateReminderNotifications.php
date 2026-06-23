<?php

namespace App\Console\Commands;

use App\Jobs\SendBookingReminderJob;
use App\Models\Booking;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('reminders:generate {--type=all : Reminder window to dispatch: day|hour|all}')]
#[Description('Dispatch booking reminder jobs for confirmed bookings (H-1 day and H-1 hour)')]
class GenerateReminderNotifications extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = (string) $this->option('type');

        $total = 0;

        if ($type === 'day' || $type === 'all') {
            $total += $this->dispatchDayReminders();
        }

        if ($type === 'hour' || $type === 'all') {
            $total += $this->dispatchHourReminders();
        }

        $this->info("✓ Dispatched {$total} reminder job(s).");

        return self::SUCCESS;
    }

    /**
     * H-1: bookings whose appointment is tomorrow.
     * Intended to run once per day.
     */
    protected function dispatchDayReminders(): int
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $bookings = Booking::query()
            ->where('status', 'confirmed')
            ->whereHas('timeSlot', function ($q) use ($tomorrow) {
                $q->whereDate('slot_date', $tomorrow);
            })
            ->get();

        foreach ($bookings as $booking) {
            SendBookingReminderJob::dispatch($booking, 'reminder_1day');
        }

        $this->line("  • H-1 day: {$bookings->count()} booking(s) for {$tomorrow}");

        return $bookings->count();
    }

    /**
     * H-1 jam: bookings whose appointment starts within the next hour.
     * Intended to run hourly (non-overlapping buckets avoid duplicate sends).
     */
    protected function dispatchHourReminders(): int
    {
        $now = Carbon::now();
        $windowEnd = $now->copy()->addHour();

        // Limit to today/tomorrow date range, then filter precisely by slot datetime.
        $bookings = Booking::query()
            ->where('status', 'confirmed')
            ->whereHas('timeSlot', function ($q) use ($now, $windowEnd) {
                $q->whereIn('slot_date', [
                    $now->toDateString(),
                    $windowEnd->toDateString(),
                ]);
            })
            ->with('timeSlot')
            ->get()
            ->filter(function (Booking $booking) use ($now, $windowEnd) {
                $slot = $booking->timeSlot;
                if (!$slot) {
                    return false;
                }

                $start = Carbon::parse(
                    $slot->slot_date->toDateString() . ' ' . $slot->slot_time
                );

                return $start->gt($now) && $start->lte($windowEnd);
            });

        foreach ($bookings as $booking) {
            SendBookingReminderJob::dispatch($booking, 'reminder_1hour');
        }

        $this->line("  • H-1 hour: {$bookings->count()} booking(s) starting before {$windowEnd->format('Y-m-d H:i')}");

        return $bookings->count();
    }
}
