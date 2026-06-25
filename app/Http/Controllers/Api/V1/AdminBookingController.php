<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Admin access required.',
                'data' => null,
                'meta' => [],
            ], 403);
        }

        $bookings = $this->bookingService->listAllBookings(
            $request->query('status'),
            (int) $request->query('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'message' => 'Bookings retrieved successfully.',
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'pagination' => [
                    'current_page' => $bookings->currentPage(),
                    'per_page' => $bookings->perPage(),
                    'total' => $bookings->total(),
                    'last_page' => $bookings->lastPage(),
                ],
            ],
        ]);
    }
}
