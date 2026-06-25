<?php

namespace Tests\Feature\Auth;

use App\Models\OTP;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpLockoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_locks_out_after_five_failed_attempts(): void
    {
        $service = app(AuthService::class);
        $phone = '081299990000';

        $service->requestOtp($phone);

        $otp = OTP::where('phone', '+6281299990000')->latest()->firstOrFail();
        $wrong = $otp->code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $result = $service->verifyOtp($phone, $wrong);
            $this->assertFalse($result['success']);
        }

        // Even the correct code is rejected once the OTP is locked.
        $locked = $service->verifyOtp($phone, $otp->code);
        $this->assertFalse($locked['success']);
        $this->assertStringContainsString('Too many', $locked['message']);
    }

    public function test_otp_uses_six_digit_code(): void
    {
        app(AuthService::class)->requestOtp('081288880000');

        $otp = OTP::where('phone', '+6281288880000')->latest()->firstOrFail();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp->code);
    }
}
