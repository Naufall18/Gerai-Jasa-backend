<?php

namespace App\Http\Controllers\Api\V1;

use App\Jobs\SendBookingConfirmationJob;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    /**
     * Handle Midtrans payment webhook.
     * POST /api/v1/webhooks/midtrans
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function midtrans(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            Log::info('Midtrans webhook received', $payload);

            // Verify Midtrans signature
            $signatureKey = $request->header('X-Midtrans-Signature');
            $orderId = $payload['order_id'] ?? '';
            $statusCode = $payload['status_code'] ?? '';
            $grossAmount = $payload['gross_amount'] ?? '';
            $serverKey = config('services.midtrans.server_key');

            $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

            if (!$signatureKey || !hash_equals($expectedSignature, $signatureKey)) {
                Log::warning('Midtrans webhook signature verification failed', [
                    'expected' => $expectedSignature,
                    'received' => $signatureKey,
                ]);
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
            }

            $status = $payload['transaction_status'] ?? null;

            if (!$orderId || !$status) {
                return response()->json(['success' => false, 'message' => 'Missing required fields'], 400);
            }

            $payment = Payment::where('gateway_ref', $orderId)->first();

            if (!$payment) {
                Log::warning('Midtrans webhook: Payment not found', ['order_id' => $orderId]);
                return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
            }

            $previousStatus = $payment->status;

            // Map Midtrans status to payment status
            match ($status) {
                'capture', 'settlement' => $payment->update(['status' => 'paid', 'paid_at' => now()]),
                'pending' => $payment->update(['status' => 'pending']),
                'deny', 'cancel', 'expire' => $payment->update(['status' => 'failed']),
                default => null,
            };

            $payment->update(['gateway_response' => $payload]);

            // Update booking status if payment successful
            if ($payment->status === 'paid' && $previousStatus !== 'paid') {
                $this->handleSuccessfulPayment($payment);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Midtrans webhook error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle Xendit payment webhook.
     * POST /api/v1/webhooks/xendit
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function xendit(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            Log::info('Xendit webhook received', $payload);

            // Verify Xendit callback token
            $callbackToken = $request->header('X-Callback-Token');
            $expectedToken = config('services.xendit.webhook_token');

            if (!$callbackToken || !hash_equals($expectedToken, $callbackToken)) {
                Log::warning('Xendit webhook callback token verification failed', [
                    'expected' => $expectedToken,
                    'received' => $callbackToken,
                ]);
                return response()->json(['success' => false, 'message' => 'Invalid callback token'], 401);
            }

            $invoiceId = $payload['external_id'] ?? null;
            $status = $payload['status'] ?? null;

            if (!$invoiceId || !$status) {
                return response()->json(['success' => false, 'message' => 'Missing required fields'], 400);
            }

            $payment = Payment::where('gateway_ref', $invoiceId)->first();

            if (!$payment) {
                Log::warning('Xendit webhook: Payment not found', ['external_id' => $invoiceId]);
                return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
            }

            $previousStatus = $payment->status;

            // Map Xendit status to payment status
            match ($status) {
                'PAID' => $payment->update(['status' => 'paid', 'paid_at' => now()]),
                'PENDING' => $payment->update(['status' => 'pending']),
                'EXPIRED', 'FAILED' => $payment->update(['status' => 'failed']),
                default => null,
            };

            $payment->update(['gateway_response' => $payload]);

            // Update booking status if payment successful
            if ($payment->status === 'paid' && $previousStatus !== 'paid') {
                $this->handleSuccessfulPayment($payment);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Xendit webhook error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle successful payment - update booking and dispatch notifications.
     *
     * @param Payment $payment
     * @return void
     */
    private function handleSuccessfulPayment(Payment $payment): void
    {
        $booking = Booking::where('id', $payment->booking_id)->first();

        if (!$booking) {
            Log::warning('Webhook: Booking not found for payment', ['payment_id' => $payment->id]);
            return;
        }

        // Only update if booking is pending
        if ($booking->status === 'pending') {
            $booking->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            // Dispatch notification job
            SendBookingConfirmationJob::dispatch($booking->id);

            Log::info('Booking confirmed via payment webhook', [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
            ]);
        }
    }
}