<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version (cache-busts on deploy).
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Shared props sent on every Inertia response.
     * Keep this LIGHT — large blobs hurt every page load.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => fn () => [
                'user' => $request->user()
                    ? $request->user()->only(['id', 'name', 'email'])
                    : null,
                // Permission/role/module flags consumed by Vue `usePermissions()`
                // composable and by `@can`-style directives in Inertia pages.
                // Lazy and cached per request — no DB hit for guests.
                'permissions' => fn () => $request->user()
                    ? $request->user()->getAllPermissions()->pluck('name')->values()->all()
                    : [],
                'roles' => fn () => $request->user()
                    ? $request->user()->getRoleNames()->values()->all()
                    : [],
                'modules' => fn () => $request->user()
                    ? Module::query()->where('is_enabled', true)->orderBy('sort_order')->pluck('slug')->values()->all()
                    : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'csrf_token' => fn () => csrf_token(),

            // Notifications: light header for the topbar dropdown / SPA layouts.
            // Lazy-evaluated so requests for guests or partial reloads stay cheap.
            'notifications' => function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return ['unread_count' => 0, 'latest' => []];
                }
                try {
                    $service = app(NotificationService::class);
                    $limit = (int) config('notifications.latest_limit', 10);
                    $latest = $service->latest($user, $limit)->map(function ($n) {
                        $data = is_array($n->data) ? $n->data : (array) $n->data;
                        return [
                            'id' => $n->id,
                            'title' => $data['title'] ?? null,
                            'message' => $data['message'] ?? 'Notification',
                            'module' => $data['module'] ?? 'SYSTEM',
                            'priority' => $data['priority'] ?? 'NORMAL',
                            'icon' => $data['icon'] ?? 'ti-bell',
                            'color' => $data['color'] ?? 'primary',
                            'url' => $data['action_url'] ?? ($data['url'] ?? '#'),
                            'time' => $n->created_at?->diffForHumans(),
                            'read' => ! is_null($n->read_at),
                        ];
                    })->values()->all();
                    return [
                        'unread_count' => $service->unreadCount($user),
                        'latest' => $latest,
                    ];
                } catch (\Throwable $e) {
                    report($e);
                    return ['unread_count' => 0, 'latest' => []];
                }
            },

            // Sidebar + topbar HTML for true Inertia pages (consumed by
            // resources/js/Layouts/AppLayout.vue). Lazy-evaluated and only
            // sent on full-page (non-partial) Inertia requests so SPA
            // navigations stay light. Skipped entirely for guests.
            'legacyChrome' => function () use ($request) {
                if (! $request->user()) {
                    return null;
                }
                if ($request->header('X-Inertia-Partial-Data')) {
                    return null;
                }
                try {
                    return View::make('layouts.partials.inertia-chrome')->render();
                } catch (\Throwable $e) {
                    report($e);
                    return null;
                }
            },
        ]);
    }
}
