<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    /**
     * Create a Midtrans Snap transaction for the given booking.
     * Returns ['order_id', 'token', 'redirect_url'].
     *
     * @throws RuntimeException when the gateway is not configured or the call fails.
     */
    public function createSnapTransaction(Booking $booking): array
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            throw new RuntimeException('Midtrans server key belum dikonfigurasi (MIDTRANS_SERVER_KEY kosong).');
        }

        $endpoint = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $orderId = $booking->booking_code;
        $grossAmount = (int) round((float) $booking->total_price);

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $booking->customer?->name ?? 'Pelanggan',
                'email' => $booking->customer?->email,
                'phone' => $booking->customer?->phone,
            ],
            'item_details' => [[
                'id' => (string) $booking->service_id,
                'price' => $grossAmount,
                'quantity' => 1,
                'name' => mb_substr($booking->service?->name ?? 'Layanan', 0, 50),
            ]],
            // The mobile WebView intercepts this URL to detect completion.
            // Midtrans appends ?order_id=&status_code=&transaction_status=.
            'callbacks' => [
                'finish' => config('services.midtrans.finish_url', 'https://geraijasa.app/payment/finish'),
            ],
        ];

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post($endpoint, $payload);

        if ($response->failed()) {
            throw new RuntimeException('Gagal membuat transaksi Midtrans: ' . $response->body());
        }

        return [
            'order_id' => $orderId,
            'token' => (string) $response->json('token'),
            'redirect_url' => (string) $response->json('redirect_url'),
        ];
    }
}
