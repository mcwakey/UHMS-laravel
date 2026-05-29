<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NotificationBroadcastController extends Controller
{
    public function create()
    {
        return view('admin.notifications.broadcast', [
            'modules' => NotificationModule::cases(),
            'priorities' => NotificationPriority::cases(),
            'roles' => Role::orderBy('name')->pluck('name'),
            'permissions' => Permission::orderBy('name')->pluck('name'),
            'departments' => \App\Models\Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, NotificationService $notifier)
    {
        $data = $request->validate([
            'target_type' => 'required|in:role,permission,department',
            'target_value' => 'required|string|max:191',
            'module' => 'required|string',
            'priority' => 'required|string',
            'title' => 'required|string|max:191',
            'message' => 'required|string|max:1000',
            'url' => 'nullable|string|max:500',
        ]);

        $payload = [
            'module' => NotificationModule::tryFrom($data['module']) ?? NotificationModule::SYSTEM,
            'priority' => NotificationPriority::tryFrom($data['priority']) ?? NotificationPriority::NORMAL,
            'title' => $data['title'],
            'message' => $data['message'],
            'url' => $data['url'] ?? '#',
            'source_type' => 'broadcast',
            'source_id' => null,
        ];

        $count = match ($data['target_type']) {
            'role' => $notifier->notifyRole($data['target_value'], $payload, 0),
            'permission' => $notifier->notifyPermission($data['target_value'], $payload, 0),
            'department' => $notifier->notifyDepartment((int) $data['target_value'], $payload, 0),
        };

        return back()->with('success', "Broadcast sent to {$count} user(s).");
    }
}
