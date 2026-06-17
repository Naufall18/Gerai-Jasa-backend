<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function __construct(
        protected VendorRepositoryInterface $vendorRepository,
        protected BookingService $bookingService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'category_id' => $request->query('category_id'),
            'city' => $request->query('city'),
            'rating' => $request->query('min_rating'),
            'search' => $request->query('search'),
        ];

        $vendors = $this->vendorRepository->list(array_filter($filters), (int) $request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'message' => 'Vendors retrieved successfully.',
            'data' => VendorResource::collection($vendors),
            'meta' => [
                'pagination' => [
                    'current_page' => $vendors->currentPage(),
                    'per_page' => $vendors->perPage(),
                    'total' => $vendors->total(),
                ],
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $vendor = $this->vendorRepository->findBySlug($slug);

        return response()->json([
            'success' => true,
            'message' => 'Vendor details retrieved successfully.',
            'data' => new VendorResource($vendor),
            'meta' => [],
        ]);
    }

    public function getSlots(string $vendorId): JsonResponse
    {
        $slots = $this->bookingService->getAvailableSlots(
            $vendorId,
            request('service_id'),
            request('date')
        );

        return response()->json([
            'success' => true,
            'message' => 'Available slots retrieved successfully.',
            'data' => $slots,
            'meta' => [],
        ]);
    }
}