<?php

namespace App\Http\Middleware;

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
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'csrf_token' => fn () => csrf_token(),

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
