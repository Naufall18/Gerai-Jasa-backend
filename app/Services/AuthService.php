<?php

namespace App\Services;

use App\Models\OTP;
use App\Models\User;
use App\Repositories\Eloquent\UserRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private NotificationService $notificationService,
    ) {
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

        // Generate 6-digit OTP using a cryptographically secure RNG.
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save OTP
        OTP::create([
            'phone' => $phone,
            'code' => $code,
            'type' => 'login',
            'expires_at' => now()->addMinutes(10),
        ]);

        // Deliver the code via WhatsApp AFTER the response is flushed, so a slow
        // gateway never blocks the API call (the mobile client has a short
        // timeout). Best-effort: no-ops without FONNTE_TOKEN, failures are
        // swallowed, and the OTP row is always readable from the DB in dev.
        defer(fn () => $this->notificationService->sendWhatsApp(
            $phone,
            "*{$code}* adalah kode OTP Gerai Jasa Anda.\n\nBerlaku 10 menit. Jangan bagikan kode ini kepada siapa pun.",
        ));

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

        // Find the latest unused, unexpired OTP for this phone (regardless of code,
        // so we can count failed attempts and lock out brute force).
        $otp = OTP::where('phone', $phone)
            ->where('type', 'login')
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ];
        }

        // Lock out after too many wrong guesses; burn the OTP so it can't be reused.
        if ($otp->attempts >= 5) {
            $otp->update(['used_at' => now()]);
            return [
                'success' => false,
                'message' => 'Too many attempts. Please request a new OTP.',
            ];
        }

        // Constant-time comparison to avoid timing leaks.
        if (!hash_equals((string) $otp->code, (string) $code)) {
            $otp->increment('attempts');
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

        // The client routes brand-new phones to the biodata/profile-setup screen.
        $isRegistered = !empty($user->email) && $user->name !== 'User';

        return [
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
                'is_registered' => $isRegistered,
            ],
        ];
    }

    /**
     * Complete the authenticated user's profile (biodata) after OTP login.
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    public function completeProfile(User $user, array $data): array
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return [
            'success' => true,
            'message' => 'Profile completed successfully',
            'data' => [
                'user' => $user->fresh(),
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
                // Default to the least-privileged role; 'admin' is rejected by RegisterRequest.
                'role' => $data['role'] ?? 'customer',
                'is_active' => true,
                // OTP customers have no password (they log in by phone); give them
                // a random unusable one so the column is never null.
                'password' => bcrypt($data['password'] ?? \Illuminate\Support\Str::random(32)),
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

        if (!Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
            ];
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        if ($user->role === 'vendor') {
            $user->loadMissing('vendor');
        }

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
        // Load the vendor profile so the dashboard knows which vendor this account
        // manages (used by vendor profile/schedule/services pages).
        if ($user->role === 'vendor') {
            $user->loadMissing('vendor');
        }

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

        if (str_starts_with($phone, '+62')) {
            return $phone;
        }
        if (str_starts_with($phone, '62')) {
            return '+' . $phone;
        }
        if (str_starts_with($phone, '0')) {
            return '+62' . substr($phone, 1);
        }

        return '+62' . $phone;
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