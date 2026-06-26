<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use App\Services\MidtransService;
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

        return $this->successResponse(
            BookingResource::collection($bookings),
            'Customer bookings retrieved successfully.',
            200,
            $this->paginationMeta($bookings)
        );
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
        $booking = $this->bookingService->createBooking((string) Auth::id(), $request->bookingData());

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => new BookingResource($booking),
            'meta' => [],
        ]);
    }

    /**
     * Create a Midtrans Snap transaction for a booking.
     * POST /api/v1/bookings/{id}/pay
     * Returns the Snap token + redirect_url for the mobile WebView.
     */
    public function pay(string $id, MidtransService $midtrans): JsonResponse
    {
        $booking = $this->bookingService->findBooking($id, (string) Auth::id());

        if ($booking->payment && $booking->payment->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Booking ini sudah dibayar.',
                'data' => null,
                'meta' => [],
            ], 422);
        }

        try {
            $snap = $midtrans->createSnapTransaction($booking);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => [],
            ], 502);
        }

        // Persist order_id so the Midtrans webhook can match this payment.
        $booking->payment?->update([
            'gateway' => 'midtrans',
            'gateway_ref' => $snap['order_id'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Snap transaction created.',
            'data' => [
                'order_id' => $snap['order_id'],
                'token' => $snap['token'],
                'redirect_url' => $snap['redirect_url'],
            ],
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