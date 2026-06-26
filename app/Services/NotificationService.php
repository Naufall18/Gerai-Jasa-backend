<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send FCM push notification to a single device.
     *
     * @param string $fcmToken
     * @param string $title
     * @param string $body
     * @param array  $data  Extra data payload for deep linking
     */
    public function sendPush(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        $serverKey = config('services.fcm.server_key');

        if (empty($serverKey) || empty($fcmToken)) {
            Log::warning('FCM: Missing server key or FCM token. Notification skipped.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "key={$serverKey}",
                'Content-Type'  => 'application/json',
            ])->connectTimeout(5)->timeout(8)->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => array_merge($data, [
                    'title' => $title,
                    'body'  => $body,
                ]),
                'priority' => 'high',
            ]);

            if ($response->successful()) {
                Log::info('FCM push sent successfully.');
                return true;
            }

            Log::warning('FCM push failed.', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('FCM push exception.', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send WhatsApp notification via Fonnte API.
     *
     * @param string $phone  Phone number in format 628xxxxxxxxx
     * @param string $message
     */
    public function sendWhatsApp(string $phone, string $message): bool
    {
        $token = config('services.fonnte.token');

        if (empty($token)) {
            Log::warning('Fonnte: Missing token. WhatsApp notification skipped.');
            return false;
        }

        // Normalize Indonesian phone number
        $phone = $this->normalizePhone($phone);

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->connectTimeout(5)->timeout(8)->post(config('services.fonnte.url'), [
                'target'  => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                // Do not log the phone number (PII).
                Log::info('WhatsApp message sent via Fonnte.');
                return true;
            }

            Log::warning('Fonnte WhatsApp failed.', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Fonnte exception.', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send booking confirmation notifications (push + WhatsApp).
     */
    public function sendBookingConfirmation(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'vendor', 'service', 'timeSlot']);

        $customer = $booking->customer;
        $vendor = $booking->vendor;
        $service = $booking->service;
        $timeSlot = $booking->timeSlot;
        $serviceName = $service?->name ?? '-';

        if (!$customer || !$vendor) {
            Log::warning('sendBookingConfirmation: Missing customer or vendor.', ['booking_id' => $booking->id]);
            return;
        }

        $title = 'Booking Dikonfirmasi! ✅';
        $body  = "Booking Anda di {$vendor->name} untuk {$service?->name} pada {$timeSlot?->slot_date} {$timeSlot?->slot_time} telah dikonfirmasi.";

        $this->record($customer, 'booking_confirmed', $title, $body, $booking);

        // FCM Push
        if (!empty($customer->fcm_token)) {
            $this->sendPush($customer->fcm_token, $title, $body, [
                'type'       => 'booking_confirmed',
                'booking_id' => $booking->id,
                'route'      => "/booking-detail/{$booking->id}",
            ]);
        }

        // WhatsApp
        if (!empty($customer->phone)) {
            $message = "✅ *Booking Dikonfirmasi!*\n\n"
                . "Halo *{$customer->name}*,\n\n"
                . "Booking Anda telah dikonfirmasi:\n"
                . "📍 Vendor: *{$vendor->name}*\n"
                . "🛎 Layanan: *{$serviceName}*\n"
                . "📅 Tanggal: *{$timeSlot?->slot_date}*\n"
                . "⏰ Jam: *{$timeSlot?->slot_time}*\n"
                . "🔖 Kode: *{$booking->booking_code}*\n\n"
                . "Terima kasih telah menggunakan Gerai Jasa!";

            $this->sendWhatsApp($customer->phone, $message);
        }
    }

    /**
     * Send booking cancellation notifications.
     */
    public function sendBookingCancellation(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'vendor', 'service', 'timeSlot']);

        $customer = $booking->customer;
        $vendor = $booking->vendor;

        if (!$customer || !$vendor) {
            return;
        }

        $title = 'Booking Dibatalkan';
        $body  = "Booking Anda di {$vendor->name} ({$booking->booking_code}) telah dibatalkan.";

        $this->record($customer, 'booking_cancelled', $title, $body, $booking);

        if (!empty($customer->fcm_token)) {
            $this->sendPush($customer->fcm_token, $title, $body, [
                'type'       => 'booking_cancelled',
                'booking_id' => $booking->id,
                'route'      => "/booking-detail/{$booking->id}",
            ]);
        }

        if (!empty($customer->phone)) {
            $reason = $booking->cancellation_reason ? "\nAlasan: {$booking->cancellation_reason}" : '';
            $message = "❌ *Booking Dibatalkan*\n\n"
                . "Booking Anda di *{$vendor->name}* ({$booking->booking_code}) telah dibatalkan.{$reason}\n\n"
                . "Hubungi kami jika ada pertanyaan.";
            $this->sendWhatsApp($customer->phone, $message);
        }
    }

    /**
     * Send booking reminder notification.
     *
     * @param string $type  'reminder_1day' | 'reminder_1hour'
     */
    public function sendBookingReminder(Booking $booking, string $type = 'reminder_1day'): void
    {
        $booking->loadMissing(['customer', 'vendor', 'service', 'timeSlot']);

        $customer = $booking->customer;
        $vendor = $booking->vendor;
        $timeSlot = $booking->timeSlot;

        if (!$customer || !$vendor || !$timeSlot) {
            return;
        }

        if ($type === 'reminder_1day') {
            $title = 'Pengingat Booking Besok 📅';
            $body  = "Jangan lupa! Besok Anda ada booking di {$vendor->name} jam {$timeSlot->slot_time}.";
            $waPrefix = "⏰ *Pengingat H-1*";
        } else {
            $title = 'Booking 1 Jam Lagi! ⏰';
            $body  = "Booking Anda di {$vendor->name} akan dimulai dalam 1 jam ({$timeSlot->slot_time}).";
            $waPrefix = "⏰ *Pengingat 1 Jam Lagi*";
        }

        $this->record($customer, 'booking_reminder', $title, $body, $booking);

        if (!empty($customer->fcm_token)) {
            $this->sendPush($customer->fcm_token, $title, $body, [
                'type'       => 'booking_reminder',
                'booking_id' => $booking->id,
                'route'      => "/booking-detail/{$booking->id}",
            ]);
        }

        if (!empty($customer->phone)) {
            $message = "{$waPrefix}\n\n"
                . "Halo *{$customer->name}*,\n\n"
                . "Ingat! Anda punya booking:\n"
                . "📍 *{$vendor->name}*\n"
                . "📅 {$timeSlot->slot_date} - ⏰ {$timeSlot->slot_time}\n"
                . "🔖 Kode: {$booking->booking_code}\n\n"
                . "Pastikan Anda hadir tepat waktu ya!";
            $this->sendWhatsApp($customer->phone, $message);
        }
    }

    /**
     * Persist an in-app notification for the user so it appears in the
     * notifications screen, independent of push/WhatsApp delivery success.
     */
    private function record(\App\Models\User $user, string $type, string $title, string $body, ?Booking $booking = null): void
    {
        \App\Models\Notification::create([
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'type' => $type,
            'data' => [
                'title' => $title,
                'body' => $body,
                'booking_id' => $booking?->id,
                'booking_code' => $booking?->booking_code,
            ],
        ]);
    }

    /**
     * Normalize phone number to international format for WhatsApp.
     * 08xxx → 628xxx
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }
        return $phone;
    }
}
