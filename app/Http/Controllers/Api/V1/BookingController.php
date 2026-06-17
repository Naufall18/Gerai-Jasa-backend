<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {
    }

    public function index(): JsonResponse
    {
        $bookings = $this->bookingService->listCustomerBookings((string) Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Customer bookings retrieved successfully.',
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'pagination' => [
                    'current_page' => $bookings->currentPage(),
                    'per_page' => $bookings->perPage(),
                    'total' => $bookings->total(),
                ],
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $booking = $this->bookingService->findBooking($id, (string) Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Booking details retrieved successfully.',
            'data' => new BookingResource($booking),
            'meta' => [],
        ]);
    }

    public function store(CreateBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->createBooking((string) Auth::id(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => new BookingResource($booking),
            'meta' => [],
        ]);
    }

    public function cancel(string $id): JsonResponse
    {
        $booking = $this->bookingService->cancelBooking($id, (string) Auth::id(), request('reason', 'Cancelled by user'));

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data' => new BookingResource($booking),
            'meta' => [],
        ]);
    }
}