<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Booking $booking,
        public string $type = 'reminder_1day' // or 'reminder_1hour'
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     *
     * TODO: Implement reminder notifications
     * - FCM push notification (H-1 dan H-0)
     * - WhatsApp via Fonnte
     */
    public function handle(): void
    {
        $booking = $this->booking->load(['customer', 'vendor', 'service', 'timeSlot']);

        if ($this->type === 'reminder_1day') {
            // TODO: Send H-1 reminder
        } elseif ($this->type === 'reminder_1hour') {
            // TODO: Send H-0 reminder (1 hour before)
        }
    }
}