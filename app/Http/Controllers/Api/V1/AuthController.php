<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\CompleteProfileRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AuthService $authService)
    {
    }

    /**
     * Request OTP for phone number.
     *
     * POST /api/v1/auth/request-otp
     *
     * @param RequestOtpRequest $request
     * @return JsonResponse
     */
    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $result = $this->authService->requestOtp($request->phone);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 429, ['retry_after' => $result['retry_after'] ?? null]);
        }

        return $this->successResponse(['phone' => $result['phone']], $result['message']);
    }

    /**
     * Verify OTP and login.
     *
     * POST /api/v1/auth/verify-otp
     *
     * @param VerifyOtpRequest $request
     * @return JsonResponse
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyOtp($request->phone, $request->code);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 401);
        }

        return $this->successResponse($result['data'], $result['message']);
    }

    /**
     * Register new user (vendor/admin).
     *
     * POST /api/v1/auth/register
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->successResponse($result['data'], $result['message'], 201);
    }

    /**
     * Login with email and password.
     *
     * POST /api/v1/auth/login
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->email, $request->password);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 401);
        }

        return $this->successResponse($result['data'], $result['message']);
    }

    /**
     * Update the authenticated user's profile.
     *
     * PATCH /api/v1/auth/profile
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $result = $this->authService->updateProfile($request->user(), $request->validated());

        return $this->successResponse($result['data'], $result['message']);
    }

    /**
     * Upload / replace the authenticated user's avatar.
     *
     * POST /api/v1/auth/avatar  (multipart: avatar=<file>)
     *
     * @param UploadAvatarRequest $request
     * @return JsonResponse
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        // Resize/cover to a 400x400 square and encode as JPEG to keep avatars small.
        $manager = new ImageManager(new GdDriver());
        $image = $manager->decodePath($request->file('avatar')->getRealPath());
        $image->cover(400, 400);
        $encoded = $image->encodeUsingFileExtension('jpg', quality: 80);

        $path = 'avatars/' . $user->id . '_' . now()->timestamp . '.jpg';
        Storage::disk('public')->put($path, (string) $encoded);

        // Remove the previous avatar file if it lived on our public disk.
        if ($user->avatar_url && str_contains($user->avatar_url, '/storage/avatars/')) {
            $old = 'avatars/' . basename(parse_url($user->avatar_url, PHP_URL_PATH));
            Storage::disk('public')->delete($old);
        }

        // Build an absolute URL using the host the client actually reached, so the
        // image loads on physical devices (LAN IP), not just localhost.
        $url = $request->getSchemeAndHttpHost() . '/storage/' . $path;
        $user->update(['avatar_url' => $url]);

        return $this->successResponse(
            ['user' => new UserResource($user->fresh())],
            'Avatar updated successfully.',
        );
    }

    /**
     * Get authenticated user.
     *
     * GET /api/v1/auth/me
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $result = $this->authService->me($request->user());

        return $this->successResponse($result['data']);
    }

    /**
     * Complete profile (biodata) for a freshly OTP-verified user.
     *
     * PATCH /api/v1/auth/complete-profile
     *
     * @param CompleteProfileRequest $request
     * @return JsonResponse
     */
    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        $result = $this->authService->completeProfile($request->user(), $request->validated());

        return $this->successResponse($result['data'], $result['message']);
    }

    /**
     * Logout user.
     *
     * POST /api/v1/auth/logout
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $result = $this->authService->logout($request->user());

        return $this->successResponse(null, $result['message']);
    }

    /**
     * Update FCM token for push notifications.
     *
     * POST /api/v1/auth/fcm-token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string|max:512',
        ]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return $this->successResponse(null, 'FCM token updated successfully.');
    }
}