<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreServiceRequest;
use App\Http\Requests\Vendor\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class VendorServiceController extends Controller
{
    private function getVendorId(): string
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $vendorId = (string) $user->vendor?->id;

        if (empty($vendorId)) {
            abort(403, 'No vendor profile associated with this account.');
        }

        return $vendorId;
    }

    /**
     * GET /api/v1/vendor/services
     */
    public function index(): JsonResponse
    {
        $vendorId = $this->getVendorId();
        $services = Service::where('vendor_id', $vendorId)->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Services retrieved.',
            'data'    => ServiceResource::collection($services),
            'meta'    => [],
        ]);
    }

    /**
     * POST /api/v1/vendor/services
     */
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $vendorId = $this->getVendorId();

        $validated = $request->validated();

        $service = Service::create([
            'vendor_id'        => $vendorId,
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'price'            => $validated['price'],
            'duration_minutes' => $validated['duration_minutes'],
            'max_advance_days' => $validated['max_advance_days'] ?? 30,
            'is_active'        => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully.',
            'data'    => new ServiceResource($service),
            'meta'    => [],
        ], 201);
    }

    /**
     * PATCH /api/v1/vendor/services/{id}
     */
    public function update(UpdateServiceRequest $request, string $id): JsonResponse
    {
        $vendorId = $this->getVendorId();
        $service  = Service::where('id', $id)->where('vendor_id', $vendorId)->firstOrFail();

        $validated = $request->validated();

        $service->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service updated.',
            'data'    => new ServiceResource($service),
            'meta'    => [],
        ]);
    }

    /**
     * DELETE /api/v1/vendor/services/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $vendorId = $this->getVendorId();
        $service  = Service::where('id', $id)->where('vendor_id', $vendorId)->firstOrFail();
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted.',
            'data'    => null,
            'meta'    => [],
        ]);
    }
}
