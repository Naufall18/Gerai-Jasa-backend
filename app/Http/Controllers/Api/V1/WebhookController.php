<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

            // TODO: Verify Midtrans signature
            // $signature = $request->header('X-Midtrans-Signature');
            // Verify against: sha512(order_id + status_code + gross_amount + server_key)

            $orderId = $payload['order_id'] ?? null;
            $status = $payload['transaction_status'] ?? null;

            if (!$orderId || !$status) {
                return response()->json(['success' => false], 400);
            }

            $payment = Payment::where('gateway_ref', $orderId)->firstOrFail();

            // Map Midtrans status to payment status
            match ($status) {
                'capture', 'settlement' => $payment->update(['status' => 'paid', 'paid_at' => now()]),
                'pending' => $payment->update(['status' => 'pending']),
                'deny', 'cancel', 'expire' => $payment->update(['status' => 'failed']),
                default => null,
            };

            $payment->update(['gateway_response' => $payload]);

            // TODO: Dispatch notification job for booking confirmation
            // TODO: Update booking status if payment successful

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Midtrans webhook error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
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

            // TODO: Verify Xendit signature
            // $signature = $request->header('X-Callback-Token');
            // Verify against configured webhook token

            $invoiceId = $payload['external_id'] ?? null;
            $status = $payload['status'] ?? null;

            if (!$invoiceId || !$status) {
                return response()->json(['success' => false], 400);
            }

            $payment = Payment::where('gateway_ref', $invoiceId)->firstOrFail();

            // Map Xendit status to payment status
            match ($status) {
                'PAID' => $payment->update(['status' => 'paid', 'paid_at' => now()]),
                'PENDING' => $payment->update(['status' => 'pending']),
                'EXPIRED', 'FAILED' => $payment->update(['status' => 'failed']),
                default => null,
            };

            $payment->update(['gateway_response' => $payload]);

            // TODO: Dispatch notification job for booking confirmation
            // TODO: Update booking status if payment successful

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Xendit webhook error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }
}