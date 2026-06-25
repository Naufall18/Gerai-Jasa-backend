<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ReplyReviewRequest;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Customer submits a review for a completed booking.
     * POST /api/v1/bookings/{id}/review
     */
    public function store(StoreReviewRequest $request, string $bookingId): JsonResponse
    {
        $customerId = (string) Auth::id();

        $booking = Booking::where('id', $bookingId)
            ->where('customer_id', $customerId)
            ->where('status', 'completed')
            ->firstOrFail();

        // Check if review already exists
        if (Review::where('booking_id', $bookingId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this booking.',
                'data' => null,
                'meta' => [],
            ], 422);
        }

        $review = DB::transaction(function () use ($request, $booking, $customerId) {
            $review = Review::create([
                'booking_id' => $booking->id,
                'vendor_id' => $booking->vendor_id,
                'customer_id' => $customerId,
                'service_id' => $booking->service_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            // Update vendor rating_avg and rating_count
            $vendor = $booking->vendor;
            if ($vendor) {
                $avg = Review::where('vendor_id', $vendor->id)->avg('rating');
                $count = Review::where('vendor_id', $vendor->id)->count();
                $vendor->update([
                    'rating_avg' => round($avg, 2),
                    'rating_count' => $count,
                ]);
            }

            return $review;
        });

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => [
                'id' => $review->id,
                'booking_id' => $review->booking_id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at->toIso8601String(),
            ],
            'meta' => [],
        ], 201);
    }

    /**
     * Get reviews for a vendor (public).
     * GET /api/v1/vendors/{vendorId}/reviews
     */
    public function vendorReviews(string $vendorId): JsonResponse
    {
        $reviews = Review::with('customer:id,name,avatar_url')
            ->where('vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->paginate(10);

        $data = $reviews->getCollection()->map(fn ($r) => [
            'id' => $r->id,
            'rating' => $r->rating,
            'comment' => $r->comment,
            'vendor_reply' => $r->vendor_reply,
            'replied_at' => $r->replied_at,
            'created_at' => $r->created_at->toIso8601String(),
            'customer' => [
                'name' => $r->customer?->name ?? 'Pelanggan',
                'avatar_url' => $r->customer?->avatar_url,
            ],
        ]);

        return $this->successResponse(
            $data,
            'Reviews retrieved successfully.',
            200,
            $this->paginationMeta($reviews)
        );
    }

    /**
     * Vendor replies to a review.
     * PATCH /api/v1/vendor/reviews/{id}/reply
     */
    public function vendorReply(ReplyReviewRequest $request, string $reviewId): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $vendorId = (string) $user->vendor?->id;

        $review = Review::where('id', $reviewId)
            ->where('vendor_id', $vendorId)
            ->firstOrFail();

        $review->update([
            'vendor_reply' => $request->reply,
            'replied_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reply submitted successfully.',
            'data' => null,
            'meta' => [],
        ]);
    }
}
