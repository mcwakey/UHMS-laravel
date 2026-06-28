<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;

/**
 * Builds the single operational-intelligence widget for a department type. Every
 * number is read from values ALREADY computed for the KPI cards (the caller passes
 * a closure) — no database access — so the widget answers "what needs attention
 * here?" at zero query cost. Each widget carries a status, a coloured level bar and
 * 2-3 supporting metric lines (e.g. "18 waiting", "3 clinicians available").
 */
class DepartmentIdentityWidgetBuilder
{
    /**
     * @param  callable(string):(int|float|array|null)  $value
     * @return array<string, mixed>|null
     */
    public function build(?DepartmentType $type, callable $value): ?array
    {
        return match ($type) {
            DepartmentType::CONSULTATION => $this->consultation($value),
            DepartmentType::EMERGENCY, DepartmentType::AMBULANCE => $this->emergency($value),
            DepartmentType::INVESTIGATION => $this->investigation($value),
            DepartmentType::RADIOLOGY => $this->radiology($value),
            DepartmentType::PHARMACY => $this->pharmacy($value),
            DepartmentType::THEATRE, DepartmentType::PROCEDURE => $this->theatre($value),
            DepartmentType::INPATIENT, DepartmentType::MATERNITY, DepartmentType::NURSING, DepartmentType::TREATMENT => $this->ward($value),
            DepartmentType::STORES, DepartmentType::BLOOD_BANK => $this->stock($value),
            DepartmentType::FINANCE, DepartmentType::ADMINISTRATIVE => $this->financial($value),
            default => $this->operational($value),
        };
    }

    // ── Task 1 — Consultation: Waiting Pressure ──────────────────────────────
    private function consultation(callable $v): array
    {
        $waiting = $this->num($v('waiting_queue'));
        $staff = $this->num($v('staff_count'));
        [$status, $variant, $level] = $this->scale($waiting, 5, 12, 20, ['low', 'moderate', 'high', 'critical']);

        return $this->assemble('waiting_pressure', 'ti-users-group', $status, $variant, $level, $this->statusLabel($status), [
            $this->metric('waiting', $waiting),
            $this->metric('clinicians', $staff),
            $this->metric('completed', $this->num($v('completed_today'))),
        ]);
    }

    // Emergency: Active Cases load
    private function emergency(callable $v): array
    {
        $active = $this->num($v('active_cases'));
        $critical = $this->num($v('critical_cases'));
        [$status, $variant, $level] = $this->scale($active, 2, 5, 9, ['low', 'moderate', 'high', 'critical']);
        if ($critical > 0) {
            [$status, $variant, $level] = ['critical', 'danger', 100];
        }

        return $this->assemble('active_load', 'ti-urgent', $status, $variant, $level, $this->statusLabel($status), [
            $this->metric('active_cases', $active),
            $this->metric('critical_cases', $critical),
        ]);
    }

    // ── Task 2 — Investigation: Turnaround Performance ───────────────────────
    private function investigation(callable $v): array
    {
        $pending = $this->num($v('pending_requests'));
        $samples = $this->num($v('samples_awaiting_acceptance'));
        $completed = $this->num($v('completed_results_today'));
        // Backlog-based performance (no timestamp queries): fewer pending = better.
        [$status, $variant, $level] = $this->backlog($pending, 8, 25);

        return $this->assemble('turnaround', 'ti-flask', $status, $variant, $level, $this->statusLabel($status), [
            $this->metric('pending', $pending),
            $this->metric('awaiting_acceptance', $samples),
            $this->metric('completed', $completed),
        ]);
    }

    // ── Task 3 — Radiology: Imaging Backlog ──────────────────────────────────
    private function radiology(callable $v): array
    {
        $pending = $this->num($v('pending_imaging'));
        $scheduled = $this->num($v('scheduled_imaging'));
        $completed = $this->num($v('completed_imaging_today'));
        [$status, $variant, $level] = $this->backlog($pending, 4, 12);

        return $this->assemble('imaging_backlog', 'ti-radioactive', $status, $variant, $level, $this->statusLabel($status), [
            $this->metric('pending_studies', $pending),
            $this->metric('scheduled', $scheduled),
            $this->metric('completed', $completed),
        ]);
    }

    // ── Task 4 — Pharmacy: Dispensing Efficiency ─────────────────────────────
    private function pharmacy(callable $v): array
    {
        $dispensed = $this->num($v('dispensed_today'));
        $pending = $this->num($v('pending_prescriptions'));
        $low = $this->num($v('low_stock'));
        $total = $dispensed + $pending;
        $pct = $total > 0 ? (int) round($dispensed / $total * 100) : 0;
        $status = $pct >= 70 ? 'excellent' : ($pct >= 40 ? 'good' : 'delayed');
        $variant = $pct >= 70 ? 'success' : ($pct >= 40 ? 'warning' : 'danger');

        return $this->assemble('dispensing_efficiency', 'ti-prescription', $status, $variant, $pct, $pct.'%', [
            $this->metric('dispensed_pct', $pct),
            $this->metric('pending', $pending),
            $this->metric('low_stock', $low),
        ]);
    }

    // ── Task 6 — Procedure / Theatre: Theatre Utilization ────────────────────
    private function theatre(callable $v): array
    {
        $active = $this->num($v('in_theatre'));
        $pending = $this->num($v('scheduled') ?: $v('pending_requests'));
        [$status, $variant, $level] = $this->scale($active, 1, 3, 5, ['normal', 'normal', 'busy', 'critical']);

        return $this->assemble('theatre_utilization', 'ti-first-aid-kit', $status, $variant, $level, $this->statusLabel($status), [
            $this->metric('in_theatre', $active),
            $this->metric('scheduled', $pending),
        ]);
    }

    // ── Task 5 — Treatment / Ward: Capacity Status ───────────────────────────
    private function ward(callable $v): array
    {
        $occupied = $this->num($v('beds_occupied'));
        $admissions = $this->num($v('active_admissions'));
        $discharges = $this->num($v('discharges_pending'));
        $load = max($occupied, $admissions);
        [$status, $variant, $level] = $this->scale($load + $discharges, 8, 18, 30, ['normal', 'normal', 'busy', 'critical']);

        return $this->assemble('capacity_status', 'ti-bed', $status, $variant, $level, $this->statusLabel($status), [
            $this->metric('occupied', $occupied),
            $this->metric('admissions', $admissions),
            $this->metric('discharge_pending', $discharges),
        ]);
    }

    // Stores / Blood bank: Stock Alerts
    private function stock(callable $v): array
    {
        $low = $this->num($v('low_stock'));
        $items = $this->num($v('stock_items'));
        [$status, $variant, $level] = $this->scale($low, 0, 5, 12, ['normal', 'moderate', 'high', 'critical']);

        return $this->assemble('stock_alerts', 'ti-packages', $status, $variant, $level, $this->statusLabel($status), [
            $this->metric('low_stock', $low),
            $this->metric('stock_items', $items),
        ]);
    }

    // ── Task 7 — Administrative / Finance: Financial Health ──────────────────
    private function financial(callable $v): array
    {
        $revenue = $v('department_revenue_today') ?? $v('revenue_today');
        if (is_array($revenue) && ($revenue['restricted'] ?? false)) {
            return $this->restricted('financial_health', 'ti-cash-banknote');
        }

        $collected = (float) (is_array($revenue) ? 0 : $revenue);
        $receivables = (float) (is_array($r = $v('receivables')) ? 0 : ($r ?? 0));
        $base = $collected + $receivables;
        $ratio = $base > 0 ? (int) round($collected / $base * 100) : 0;
        $status = $ratio >= 70 ? 'excellent' : ($ratio >= 40 ? 'good' : 'delayed');
        $variant = $ratio >= 70 ? 'success' : ($ratio >= 40 ? 'warning' : 'danger');

        return $this->assemble('financial_health', 'ti-cash-banknote', $status, $variant, $ratio, $ratio.'%', [
            $this->metricAmount('collected', $collected),
            $this->metricAmount('receivables', $receivables),
            $this->metric('payment_ratio', $ratio),
        ]);
    }

    // ── Task 8 — Support: Operational Load ───────────────────────────────────
    private function operational(callable $v): array
    {
        $visits = $this->num($v('visits_today'));
        $activity = $this->num($v('activity_today'));
        [$status, $variant, $level] = $this->scale(max($visits, $activity), 10, 30, 60, ['low', 'moderate', 'high', 'critical']);

        return $this->assemble('operational_load', 'ti-activity', $status, $variant, $level, $this->statusLabel($status), [
            $this->metric('visits', $visits),
            $this->metric('activity', $activity),
        ]);
    }

    // ── Assembly helpers ─────────────────────────────────────────────────────

    /** @param list<array{key:string,text:string}> $metrics */
    private function assemble(string $key, string $icon, string $status, string $variant, int $level, string $value, array $metrics): array
    {
        return [
            'key' => $key,
            'title' => __('dashboards.department.widget.'.$key),
            'icon' => $icon,
            'variant' => $variant,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'value' => $value,
            'caption' => $this->statusLabel($status),
            'level' => $level,
            'metrics' => $metrics,
            'restricted' => false,
        ];
    }

    private function restricted(string $key, string $icon): array
    {
        return [
            'key' => $key,
            'title' => __('dashboards.department.widget.'.$key),
            'icon' => $icon,
            'variant' => 'secondary',
            'status' => 'restricted',
            'status_label' => __('dashboards.department.restricted'),
            'value' => __('dashboards.department.restricted'),
            'caption' => __('dashboards.department.restricted'),
            'level' => null,
            'metrics' => [],
            'restricted' => true,
        ];
    }

    /** @return array{key:string,text:string} */
    private function metric(string $key, int $count): array
    {
        return ['key' => $key, 'text' => __('dashboards.department.widget.metric.'.$key, ['count' => $count])];
    }

    private function metricAmount(string $key, float $amount): array
    {
        return ['key' => $key, 'text' => __('dashboards.department.widget.metric.'.$key, ['amount' => '₵'.number_format($amount, 2)])];
    }

    private function statusLabel(string $status): string
    {
        return __('dashboards.department.widget.status.'.$status);
    }

    /**
     * Four-band scale by count thresholds. $labels = [<=a, <=b, <=c, >c].
     *
     * @return array{0:string,1:string,2:int}  [statusCode, variant, level%]
     */
    private function scale(int $n, int $a, int $b, int $c, array $labels): array
    {
        return match (true) {
            $n <= $a => [$labels[0], $this->variantFor($labels[0]), 25],
            $n <= $b => [$labels[1], $this->variantFor($labels[1]), 55],
            $n <= $c => [$labels[2], $this->variantFor($labels[2]), 80],
            default => [$labels[3], $this->variantFor($labels[3]), 100],
        };
    }

    /** Backlog → performance band (fewer pending is better). */
    private function backlog(int $pending, int $good, int $delayed): array
    {
        return match (true) {
            $pending <= $good => ['excellent', 'success', 90],
            $pending <= $delayed => ['good', 'info', 60],
            default => ['delayed', 'danger', 30],
        };
    }

    private function variantFor(string $status): string
    {
        return match ($status) {
            'low', 'normal', 'excellent', 'clear' => 'success',
            'moderate', 'good', 'busy' => 'warning',
            'high', 'delayed' => 'danger',
            'critical' => 'danger',
            default => 'secondary',
        };
    }

    private function num(int|float|array|null $value): int
    {
        return is_array($value) ? 0 : (int) round((float) $value);
    }
}
