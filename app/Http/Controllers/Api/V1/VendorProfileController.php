<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateProfileRequest;
use App\Http\Requests\Vendor\UpdateSchedulesRequest;
use App\Http\Resources\VendorResource;
use App\Models\Schedule;
use App\Models\Vendor;
use App\Services\SlotGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorProfileController extends Controller
{
    private function getVendor(): Vendor
    {
        /** @var \App\Models\User $user */
        $user   = Auth::user();
        $vendor = $user->vendor;

        if (!$vendor) {
            abort(403, 'No vendor profile associated with this account.');
        }

        return $vendor;
    }

    /**
     * GET /api/v1/vendor/profile
     * Returns the authenticated vendor's full profile.
     */
    public function show(): JsonResponse
    {
        $vendor = $this->getVendor();
        $vendor->loadMissing(['category', 'services', 'photos', 'schedules']);

        return response()->json([
            'success' => true,
            'message' => 'Vendor profile retrieved.',
            'data'    => new VendorResource($vendor),
            'meta'    => [],
        ]);
    }

    /**
     * PATCH /api/v1/vendor/profile
     * Updates name, description, address, city.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $vendor = $this->getVendor();

        $validated = $request->validated();

        $vendor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendor profile updated.',
            'data'    => new VendorResource($vendor),
            'meta'    => [],
        ]);
    }

    /**
     * PATCH /api/v1/vendor/schedules
     * Bulk-upsert operating schedules.
     * Body: { schedules: [{ day_of_week, open_time, close_time, is_closed }] }
     */
    public function updateSchedules(
        UpdateSchedulesRequest $request,
        SlotGenerationService $slotService
    ): JsonResponse {
        $vendor = $this->getVendor();

        $validated = $request->validated();

        DB::transaction(function () use ($vendor, $validated) {
            foreach ($validated['schedules'] as $item) {
                Schedule::updateOrCreate(
                    ['vendor_id' => $vendor->id, 'day_of_week' => $item['day_of_week']],
                    [
                        'open_time'  => $item['open_time'],
                        'close_time' => $item['close_time'],
                        'is_closed'  => $item['is_closed'],
                    ]
                );
            }
        });

        // Regenerate time slots so the calendar reflects the updated schedule
        $slotService->generateForVendor($vendor, days: 60);

        return response()->json([
            'success' => true,
            'message' => 'Schedules updated.',
            'data'    => null,
            'meta'    => [],
        ]);
    }
}
