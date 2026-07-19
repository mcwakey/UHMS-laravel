<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ModuleService
{
    protected const CACHE_KEY = 'uhms.modules.enabled';
    protected const CACHE_TTL = 3600; // 1 hour

    /** @var array<string, bool>|null */
    private ?array $resolvedModules = null;

    /**
     * Return all modules cached as slug => bool.
     */
    public function all(): array
    {
        return $this->resolvedModules ??= Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
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
        $module = Module::where('slug', $slug)->first();
        Module::where('slug', $slug)->update(['is_enabled' => true]);
        $this->flush();

        if ($module && ! $module->is_enabled) {
            $this->logModuleToggle($module, true);
        }
    }

    public function disable(string $slug): void
    {
        $module = Module::where('slug', $slug)->first();
        if (! $module || $module->is_core) {
            return; // cannot disable core modules
        }
        $module->update(['is_enabled' => false]);
        $this->flush();

        $this->logModuleToggle($module, false);
    }

    private function logModuleToggle(Module $module, bool $enabled): void
    {
        try {
            app(\App\Services\ActivityLogService::class)->log(
                \App\Enums\LogModule::SETTINGS,
                $enabled ? 'MODULE_ENABLED' : 'MODULE_DISABLED',
                [
                    'module_id' => $module->id,
                    'severity' => \App\Enums\LogSeverity::WARNING,
                    'old_values' => ['is_enabled' => ! $enabled],
                    'new_values' => ['is_enabled' => $enabled],
                    'source_type' => 'module',
                    'source_id' => $module->id,
                ],
                $module,
                ($enabled ? 'Module enabled: ' : 'Module disabled: ') . ($module->name ?: $module->slug),
            );
        } catch (\Throwable $e) {
            // Logging must never break module toggling.
        }
    }

    public function flush(): void
    {
        $this->resolvedModules = null;
        Cache::forget(self::CACHE_KEY);
    }

    public function list(): Collection
    {
        return Module::query()->orderBy('sort_order')->get();
    }

    /**
     * Fetch a single module record by slug (for friendly disabled-module UI).
     */
    public function find(string $slug): ?Module
    {
        return Module::where('slug', $slug)->first();
    }
}
