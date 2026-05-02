<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ModuleService
{
    protected const CACHE_KEY = 'uhms.modules.enabled';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Return all modules cached as slug => bool.
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Module::query()
                ->orderBy('sort_order')
                ->get()
                ->mapWithKeys(fn ($m) => [$m->slug => (bool) $m->is_enabled])
                ->all();
        });
    }

    /**
     * Whether a module is enabled.
     * Unknown modules default to TRUE so the system fails-open
     * (no surprises if a slug is checked before being seeded).
     */
    public function enabled(string $slug): bool
    {
        $map = $this->all();
        return $map[$slug] ?? true;
    }

    public function disabled(string $slug): bool
    {
        return ! $this->enabled($slug);
    }

    public function enable(string $slug): void
    {
        Module::where('slug', $slug)->update(['is_enabled' => true]);
        $this->flush();
    }

    public function disable(string $slug): void
    {
        $module = Module::where('slug', $slug)->first();
        if (! $module || $module->is_core) {
            return; // cannot disable core modules
        }
        $module->update(['is_enabled' => false]);
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function list(): Collection
    {
        return Module::query()->orderBy('sort_order')->get();
    }
}
