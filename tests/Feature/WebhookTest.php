<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMarketplaceData;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase, CreatesMarketplaceData;

    private function pendingBookingWithPayment(): array
    {
        $customer = \App\Models\User::factory()->create(['role' => 'customer']);
        $vendor = $this->makeVendor();
        $service = $this->makeService($vendor);
        $slot = $this->makeSlot($vendor, $service);
        $booking = $this->makeBooking($customer, $vendor, $service, $slot, 'pending', 'midtrans');
        $payment = $this->makePayment($booking, 'pending');

        return [$booking, $payment];
    }

    public function test_midtrans_webhook_rejects_invalid_signature(): void
    {
        config(['services.midtrans.server_key' => 'secret']);
        [$booking] = $this->pendingBookingWithPayment();

        $res = $this->postJson('/api/v1/webhooks/midtrans', [
            'order_id' => $booking->booking_code,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
        ], ['X-Midtrans-Signature' => 'definitely-wrong']);

        $res->assertStatus(401);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
    }

    public function test_midtrans_webhook_confirms_booking_on_valid_signature(): void
    {
        config(['services.midtrans.server_key' => 'secret']);
        [$booking, $payment] = $this->pendingBookingWithPayment();

        $orderId = $booking->booking_code;
        $statusCode = '200';
        $gross = '100000.00';
        $signature = hash('sha512', $orderId . $statusCode . $gross . 'secret');

        $res = $this->postJson('/api/v1/webhooks/midtrans', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $gross,
            'transaction_status' => 'settlement',
        ], ['X-Midtrans-Signature' => $signature]);

        $res->assertStatus(200);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    public function test_midtrans_webhook_fails_closed_when_secret_missing(): void
    {
        config(['services.midtrans.server_key' => '']);
        [$booking] = $this->pendingBookingWithPayment();

        $res = $this->postJson('/api/v1/webhooks/midtrans', [
            'order_id' => $booking->booking_code,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
        ], ['X-Midtrans-Signature' => 'anything']);

        $res->assertStatus(500);
    }
}
