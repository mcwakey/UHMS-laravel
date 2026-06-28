<?php

namespace App\Services\Department;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * Turns a raw status string (e.g. "queued", "pending") into a localized label, a
 * Bootstrap colour variant and an icon — reusing the SAME maps as the shared
 * <x-status-badge> component (config/ui.php + lang/statuses.php), so colours and
 * translations stay consistent app-wide. Pure config/lang lookups, no queries.
 */
class DepartmentStatusPresenter
{
    /**
     * @return array{label: ?string, variant: string, icon: ?string, priority: bool}
     */
    public function present(?string $status, string $domain = 'default'): array
    {
        $raw = trim((string) $status);
        if ($raw === '') {
            return ['label' => null, 'variant' => 'secondary', 'icon' => null, 'priority' => false];
        }

        $key = strtoupper($raw);
        $variant = config("ui.status.{$domain}.{$key}")
            ?? config("ui.status.default.{$key}")
            ?? config('ui.fallback_variant', 'secondary');

        $lang = strtolower($raw);
        if (Lang::has("statuses.{$domain}.{$lang}")) {
            $label = __("statuses.{$domain}.{$lang}");
        } elseif (Lang::has("statuses.default.{$lang}")) {
            $label = __("statuses.default.{$lang}");
        } else {
            $label = Str::of($raw)->replace(['_', '-'], ' ')->title()->value();
        }

        return [
            'label' => $label,
            'variant' => $variant,
            'icon' => $this->iconForVariant($variant),
            // Critical / overdue / emergency statuses resolve to "danger" → priority.
            'priority' => $variant === 'danger',
        ];
    }

    private function iconForVariant(string $variant): string
    {
        return match ($variant) {
            'success' => 'ti-circle-check',
            'danger' => 'ti-alert-triangle',
            'warning' => 'ti-clock-hour-4',
            'info', 'primary' => 'ti-progress',
            'indigo' => 'ti-hourglass',
            default => 'ti-point-filled',
        };
    }
}
