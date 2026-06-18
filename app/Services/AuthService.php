<?php

namespace App\Services;

use App\Models\OTP;
use App\Models\User;
use App\Repositories\Eloquent\UserRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /**
     * Request OTP for phone number.
     *
     * @param string $phone
     * @return array
     */
    public function requestOtp(string $phone): array
    {
        // Clean phone number
        $phone = $this->normalizePhone($phone);

        // Delete expired OTPs
        OTP::where('phone', $phone)->where('expires_at', '<', now())->delete();

        // Check if OTP already sent in last 60 seconds
        $recentOtp = OTP::where('phone', $phone)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($recentOtp && $recentOtp->created_at->diffInSeconds(now()) < 60) {
            return [
                'success' => false,
                'message' => 'Please wait before requesting a new OTP',
                'retry_after' => 60 - $recentOtp->created_at->diffInSeconds(now()),
            ];
        }

        // Generate 6-digit OTP
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save OTP
        OTP::create([
            'phone' => $phone,
            'code' => $code,
            'type' => 'login',
            'expires_at' => now()->addMinutes(10),
        ]);

        return [
            'success' => true,
            'message' => 'OTP sent successfully',
            'phone' => $this->maskPhone($phone),
        ];
    }

    /**
     * Verify OTP and login user.
     *
     * @param string $phone
     * @param string $code
     * @return array
     */
    public function verifyOtp(string $phone, string $code): array
    {
        $phone = $this->normalizePhone($phone);

        // Find valid OTP
        $otp = OTP::where('phone', $phone)
            ->where('code', $code)
            ->where('type', 'login')
            ->where('expires_at', '>', now())
            ->where('used_at', null)
            ->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ];
        }

        // Mark OTP as used
        $otp->update(['used_at' => now()]);

        // Find or create user
        $user = $this->userRepository->findByPhone($phone);

        if (!$user) {
            $user = $this->userRepository->create([
                'phone' => $phone,
                'name' => 'User',
                'role' => 'customer',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]);
        } else {
            $user->update(['phone_verified_at' => now()]);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ];
    }

    /**
     * Register new user (vendor/admin).
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $this->normalizePhone($data['phone']),
                'role' => $data['role'] ?? 'vendor',
                'is_active' => true,
                'password' => bcrypt($data['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ];
        });
    }

    /**
     * Login with email and password.
     *
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$user->is_active) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
            ];
        }

        if (!\Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
            ];
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ];
    }

    /**
     * Get authenticated user.
     *
     * @param User $user
     * @return array
     */
    public function me(User $user): array
    {
        return [
            'success' => true,
            'data' => $user,
        ];
    }

    /**
     * Logout user.
     *
     * @param User $user
     * @return array
     */
    public function logout(User $user): array
    {
        $user->tokens()->delete();

        return [
            'success' => true,
            'message' => 'Logged out successfully',
        ];
    }

    /**
     * Normalize phone number to Indonesian format.
     *
     * @param string $phone
     * @return string
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '+62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '+62')) {
            $phone = '+62' . $phone;
        }

        return $phone;
    }

    /**
     * Mask phone number for display.
     *
     * @param string $phone
     * @return string
     */
    private function maskPhone(string $phone): string
    {
        return substr($phone, 0, -4) . '****';
    }
}