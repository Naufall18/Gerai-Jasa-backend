<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    /**
     * Get a paginated list of all users (admin only).
     * GET /api/v1/admin/users
     */
    public function index(Request $request): JsonResponse
    {
        // Authorization enforced by the 'role:admin' route middleware.
        $query = User::query();

        // Optional filters
        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 20));

        return $this->successResponse(
            UserResource::collection($users),
            'Users retrieved successfully.',
            200,
            $this->paginationMeta($users)
        );
    }
}
