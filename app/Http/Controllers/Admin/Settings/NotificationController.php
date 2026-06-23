<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $service)
    {
    }

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $filterRead = $request->query('read');     // 'unread' | 'read' | null
        $filterModule = $request->query('module'); // enum value
        $filterPriority = $request->query('priority');

        $query = $user->notifications();

        if ($filterRead === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filterRead === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($filterModule && NotificationModule::tryFrom($filterModule)) {
            $query->where('data', 'like', '%"module":"' . $filterModule . '"%');
        }

        if ($filterPriority && NotificationPriority::tryFrom($filterPriority)) {
            $query->where('data', 'like', '%"priority":"' . $filterPriority . '"%');
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'filterRead' => $filterRead,
            'filterModule' => $filterModule,
            'filterPriority' => $filterPriority,
            'moduleOptions' => NotificationModule::cases(),
            'priorityOptions' => NotificationPriority::cases(),
        ]);
    }

    public function recent(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $notifications = $user->unreadNotifications()
            ->latest()
            ->take((int) config('notifications.latest_limit', 10))
            ->get()
            ->map(function ($notification) {
                $data = is_array($notification->data) ? $notification->data : (array) $notification->data;
                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? null,
                    'message' => $data['message'] ?? 'Notification',
                    'module' => $data['module'] ?? 'SYSTEM',
                    'priority' => $data['priority'] ?? 'NORMAL',
                    'url' => $data['action_url'] ?? ($data['url'] ?? '#'),
                    'icon' => $data['icon'] ?? 'ti-bell',
                    'color' => $data['color'] ?? 'primary',
                    'type' => $data['type'] ?? 'general',
                    'time' => $notification->created_at->diffForHumans(),
                    'read' => ! is_null($notification->read_at),
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $this->service->unreadCount($user),
        ]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $ok = $this->service->markAsRead($user, $id);

        return response()->json(['success' => $ok], $ok ? 200 : 404);
    }

    public function markAllAsRead(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $count = $this->service->markAllAsRead($user);

        return response()->json(['success' => true, 'updated' => $count]);
    }

    public function destroy(string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $notification = $user->notifications()->whereKey($id)->first();
        if (! $notification) {
            return response()->json(['success' => false], 404);
        }
        $notification->delete();
        return response()->json(['success' => true]);
    }
}

