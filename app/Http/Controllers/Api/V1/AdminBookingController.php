<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {
    }

    /**
     * Get a paginated list of all bookings across the platform (admin only).
     * GET /api/v1/admin/bookings
     */
    public function index(Request $request): JsonResponse
    {
        // Authorization enforced by the 'role:admin' route middleware.
        $request->validate([
            'status' => 'sometimes|in:pending,confirmed,in_progress,completed,cancelled,awaiting_payment',
        ]);

        $bookings = $this->bookingService->listAllBookings(
            $request->query('status'),
            (int) $request->query('per_page', 20)
        );

        return $this->successResponse(
            BookingResource::collection($bookings),
            'Bookings retrieved successfully.',
            200,
            $this->paginationMeta($bookings)
        );
    }
}
