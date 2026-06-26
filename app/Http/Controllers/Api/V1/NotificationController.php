<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List the authenticated user's notifications (paginated, newest first).
     * GET /api/v1/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = Notification::where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 20));

        $unread = Notification::where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return $this->successResponse(
            NotificationResource::collection($notifications),
            'Notifications retrieved successfully.',
            200,
            array_merge($this->paginationMeta($notifications), ['unread_count' => $unread]),
        );
    }

    /**
     * Mark a single notification as read.
     * PATCH /api/v1/notifications/{id}/read
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        return $this->successResponse(null, 'Notification marked as read.');
    }

    /**
     * Mark all of the user's notifications as read.
     * PATCH /api/v1/notifications/read-all
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        Notification::where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->successResponse(null, 'All notifications marked as read.');
    }
}
