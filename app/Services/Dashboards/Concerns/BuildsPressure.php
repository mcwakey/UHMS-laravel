<?php

namespace App\Services\Dashboards\Concerns;

/**
 * Builds the "pressure" widget payload for role dashboards — the same shape the
 * department identity-widget renders (title, status, progress level, metric
 * chips) so all dashboards share one visual grammar.
 */
trait BuildsPressure
{
    /**
     * Four-band load scale (mirrors DepartmentIdentityWidgetBuilder::scale()).
     *
     * @param  array{0:int,1:int,2:int}  $thresholds  [low<=, moderate<=, high<=]
     * @param  array<int, string>  $metrics  already-translated metric texts
     * @return array<string, mixed>
     */
    private function pressure(string $title, string $icon, int $load, array $thresholds, array $metrics): array
    {
        [$a, $b, $c] = $thresholds;
        [$status, $variant, $level] = match (true) {
            $load <= $a => ['low', 'success', 25],
            $load <= $b => ['moderate', 'warning', 55],
            $load <= $c => ['high', 'danger', 80],
            default => ['critical', 'danger', 100],
        };

        return [
            'title' => $title,
            'icon' => $icon,
            'variant' => $variant,
            'status' => $status,
            'value' => __('dashboards.department.widget.status.'.$status),
            'level' => $level,
            'metrics' => array_map(fn (string $text) => ['text' => $text], $metrics),
        ];
    }
}
