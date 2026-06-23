<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function store(Request $request): JsonResponse
    {
        $vendorId = $this->getVendorId();

        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string|max:500',
            'price'            => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'max_advance_days' => 'nullable|integer|min:1|max:365',
        ]);

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
    public function update(Request $request, string $id): JsonResponse
    {
        $vendorId = $this->getVendorId();
        $service  = Service::where('id', $id)->where('vendor_id', $vendorId)->firstOrFail();

        $validated = $request->validate([
            'name'             => 'sometimes|string|max:100',
            'description'      => 'nullable|string|max:500',
            'price'            => 'sometimes|numeric|min:0',
            'duration_minutes' => 'sometimes|integer|min:15|max:480',
            'max_advance_days' => 'nullable|integer|min:1|max:365',
            'is_active'        => 'sometimes|boolean',
        ]);

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
