<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Booking $booking,
        public string $type = 'reminder_1day' // 'reminder_1day' | 'reminder_1hour'
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationService $notificationService): void
    {
        // Don't send reminder if booking is no longer active
        if (!in_array($this->booking->status, ['confirmed', 'pending'])) {
            return;
        }

        $notificationService->sendBookingReminder($this->booking, $this->type);
    }

    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('SendBookingReminderJob failed', [
            'booking_id' => $this->booking->id,
            'type'       => $this->type,
            'error'      => $exception->getMessage(),
        ]);
    }
}
