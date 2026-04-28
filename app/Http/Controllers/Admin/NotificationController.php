<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $notifications = $user
            ->notifications()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function recent(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $notifications = $user->unreadNotifications()
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'message' => $notification->data['message'] ?? 'Notification',
                    'url' => $notification->data['url'] ?? '#',
                    'icon' => $notification->data['icon'] ?? 'ti-bell',
                    'color' => $notification->data['color'] ?? 'primary',
                    'type' => $notification->data['type'] ?? 'general',
                    'time' => $notification->created_at->diffForHumans(),
                    'read' => !is_null($notification->read_at),
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $notification = $authUser
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $currentUser->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
