<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Readiness probe: verifies the app can actually serve traffic
     * (database + cache reachable). Distinct from the '/up' liveness check.
     * GET /api/v1/health/ready
     */
    public function ready(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        try {
            DB::select('select 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail';
            $healthy = false;
        }

        try {
            Cache::put('health:ready', 1, 5);
            $checks['cache'] = Cache::get('health:ready') === 1 ? 'ok' : 'fail';
            $healthy = $healthy && $checks['cache'] === 'ok';
        } catch (\Throwable $e) {
            $checks['cache'] = 'fail';
            $healthy = false;
        }

        return response()->json([
            'success' => $healthy,
            'message' => $healthy ? 'Ready' : 'Degraded',
            'data' => [
                'status' => $healthy ? 'ready' : 'degraded',
                'checks' => $checks,
            ],
            'meta' => [],
        ], $healthy ? 200 : 503);
    }
}
