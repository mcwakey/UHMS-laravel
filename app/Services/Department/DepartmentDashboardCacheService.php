<?php

namespace App\Services\Department;

use Illuminate\Support\Facades\Cache;

/**
 * Short-TTL cache for the assembled department dashboard payload. Repeated loads of
 * the same dashboard (auto-refresh, navigation, quick re-visits) reuse the cached
 * result instead of recomputing every metric/chart/queue.
 *
 * Isolation: the cache key is scoped per department, per dashboard type/key AND per
 * user — metric/stock/revenue visibility depends on the viewer's permissions, so a
 * cached payload is never shared across users. Entries expire automatically; no
 * manual clearing is required.
 *
 * The TTL is a single, conservative value (the strictest of the per-section targets)
 * so nothing is ever served staler than that window.
 */
class DepartmentDashboardCacheService
{
    /** Seconds. Queues are the most time-sensitive section, so we use their window. */
    public const TTL = 30;

    /** Explicit override of the enabled state (null = auto: on except in tests). */
    private static ?bool $enabled = null;

    public static function enable(): void
    {
        self::$enabled = true;
    }

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function reset(): void
    {
        self::$enabled = null;
    }

    /**
     * Return the cached payload for this context, or build + cache it.
     *
     * @param  callable():array<string,mixed>  $build
     * @return array<string, mixed>
     */
    public function remember(DepartmentDashboardContext $context, callable $build): array
    {
        if (! $this->enabled()) {
            return $build();
        }

        return Cache::remember($this->keyFor($context), self::TTL, $build);
    }

    public function forget(DepartmentDashboardContext $context): void
    {
        Cache::forget($this->keyFor($context));
    }

    public function keyFor(DepartmentDashboardContext $context): string
    {
        return implode(':', [
            'dept_dashboard',
            $context->department_id ?? 'global',
            $context->dashboard_key,
            'u'.$context->user->getKey(),
        ]);
    }

    private function enabled(): bool
    {
        // Default: enabled everywhere except automated tests (where reused, rolled-back
        // department IDs would otherwise collide). The performance test opts in.
        return self::$enabled ?? ! app()->runningUnitTests();
    }
}
