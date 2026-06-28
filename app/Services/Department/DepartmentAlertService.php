<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;

/**
 * Derives "what needs attention right now" alerts and an overall operational status
 * (Stable / Busy / Critical) purely from values ALREADY computed for the KPI cards —
 * no database polling. Because those values are capability-gated, a user who can't
 * see a domain never triggers its alert (a restricted value reads as 0).
 */
class DepartmentAlertService
{
    /**
     * @param  callable(string):(int|float|array|null)  $value
     * @return list<array{key:string,message:string,variant:string,icon:string}>
     */
    public function alertsFor(?DepartmentType $type, callable $value): array
    {
        $v = fn (string $key): int => $this->num($value($key));
        $alerts = [];

        if (($n = $v('critical_cases')) >= 1) {
            $alerts[] = $this->alert('critical_cases', $n, 'danger', 'ti-urgent');
        }
        if (($n = $v('waiting_queue')) >= 15) {
            $alerts[] = $this->alert('excessive_waiting', $n, $n >= 30 ? 'danger' : 'warning', 'ti-clock-exclamation');
        }
        if (($n = $v('low_stock')) >= 1) {
            $alerts[] = $this->alert('stock_shortage', $n, 'danger', 'ti-package-off');
        }
        if (($n = $v('pending_requests')) >= 20) {
            $alerts[] = $this->alert('overdue_requests', $n, 'warning', 'ti-file-alert');
        }
        if (($n = $v('pending_imaging')) >= 12) {
            $alerts[] = $this->alert('imaging_backlog', $n, 'warning', 'ti-photo-exclamation');
        }
        if (($n = $v('pending_prescriptions')) >= 15) {
            $alerts[] = $this->alert('dispensing_backlog', $n, 'warning', 'ti-prescription');
        }
        if (($n = $v('discharges_pending')) >= 5) {
            $alerts[] = $this->alert('discharge_pressure', $n, 'warning', 'ti-bed-off');
        }

        return $alerts;
    }

    /**
     * @param  list<array<string,mixed>>  $alerts
     * @return array{level:string,label:string,variant:string}
     */
    public function statusFor(?DepartmentType $type, callable $value, array $alerts): array
    {
        $hasCritical = false;
        foreach ($alerts as $alert) {
            if (($alert['variant'] ?? null) === 'danger') {
                $hasCritical = true;
                break;
            }
        }

        $level = $hasCritical ? 'critical' : (count($alerts) > 0 ? 'busy' : 'stable');

        return [
            'level' => $level,
            'label' => __('dashboards.department.op_status.'.$level),
            'variant' => match ($level) {
                'critical' => 'danger',
                'busy' => 'warning',
                default => 'success',
            },
        ];
    }

    private function alert(string $key, int $count, string $variant, string $icon): array
    {
        return [
            'key' => $key,
            'message' => __('dashboards.department.alerts.'.$key, ['count' => $count]),
            'variant' => $variant,
            'icon' => $icon,
        ];
    }

    private function num(int|float|array|null $value): int
    {
        return is_array($value) ? 0 : (int) round((float) $value);
    }
}
