<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param array $meta
     * @return JsonResponse
     */
    public function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $data
     * @param array $meta
     * @return JsonResponse
     */
    public function errorResponse(
        string $message = 'Error',
        int $statusCode = 400,
        mixed $data = null,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $statusCode);
    }

    /**
     * Build the standard nested pagination meta block.
     * Shape: meta.pagination.{current_page, per_page, total, last_page}
     *
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     * @return array
     */
    public function paginationMeta(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator): array
    {
        return [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    /**
     * Return a paginated JSON response using the standard envelope + nested meta.
     *
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     * @param mixed $data  Already-transformed data (e.g. a Resource collection).
     * @param string $message
     * @return JsonResponse
     */
    public function paginatedResponse(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator, mixed $data = null, string $message = 'Success'): JsonResponse
    {
        return $this->successResponse(
            $data ?? $paginator->items(),
            $message,
            200,
            $this->paginationMeta($paginator)
        );
    }
}