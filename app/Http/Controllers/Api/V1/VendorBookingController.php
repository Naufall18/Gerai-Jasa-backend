<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class VendorBookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {
    }

    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $vendorId = $user ? (string) $user->vendor?->id : '';

        $bookings = $this->bookingService->listVendorBookings($vendorId);

        return $this->successResponse(
            BookingResource::collection($bookings),
            'Vendor bookings retrieved successfully.',
            200,
            $this->paginationMeta($bookings)
        );
    }

    public function confirm(string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $vendorId = $user ? (string) $user->vendor?->id : '';

        $booking = $this->bookingService->confirmBooking($id, $vendorId);

        return response()->json([
            'success' => true,
            'message' => 'Booking confirmed successfully.',
            'data' => new BookingResource($booking),
            'meta' => [],
        ]);
    }

    public function complete(string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $vendorId = $user ? (string) $user->vendor?->id : '';

        $booking = $this->bookingService->completeBooking($id, $vendorId);

        return response()->json([
            'success' => true,
            'message' => 'Booking completed successfully.',
            'data' => new BookingResource($booking),
            'meta' => [],
        ]);
    }
}