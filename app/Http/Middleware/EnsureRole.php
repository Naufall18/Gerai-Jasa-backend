<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorize a request by the user's `role` column.
 *
 * This app stores the role directly on the users table (not via spatie's
 * model_has_roles tables), so we enforce it explicitly here.
 *
 * Usage: ->middleware('role:admin') / ->middleware('role:vendor')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. ' . ucfirst($role) . ' access required.',
                'data' => null,
                'meta' => [],
            ], 403);
        }

        return $next($request);
    }
}
