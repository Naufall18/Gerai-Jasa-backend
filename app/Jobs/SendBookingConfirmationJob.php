<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Booking $booking
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     *
     * TODO: Implement notification dispatch to customer
     * - FCM push notification
     * - WhatsApp via Fonnte
     * - Email via Mailgun
     */
    public function handle(): void
    {
        $booking = $this->booking->load(['customer', 'vendor', 'service', 'timeSlot']);

        // TODO: Send FCM push
        // TODO: Send WhatsApp message
        // TODO: Send email notification
    }
}