<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;

class DepartmentDashboardThemeRegistry
{
    private const THEMES = [
        'consultation' => ['accent_class' => 'primary', 'soft_bg_class' => 'primary-subtle', 'icon' => 'ti-stethoscope', 'hero_icon' => 'ti-stethoscope', 'chart_accent' => '#0d6efd', 'badge_class' => 'bg-primary', 'empty_state_icon' => 'ti-stethoscope'],
        'emergency' => ['accent_class' => 'danger', 'soft_bg_class' => 'danger-subtle', 'icon' => 'ti-ambulance', 'hero_icon' => 'ti-ambulance', 'chart_accent' => '#dc3545', 'badge_class' => 'bg-danger', 'empty_state_icon' => 'ti-ambulance'],
        'investigation' => ['accent_class' => 'info', 'soft_bg_class' => 'info-subtle', 'icon' => 'ti-test-pipe', 'hero_icon' => 'ti-microscope', 'chart_accent' => '#6610f2', 'badge_class' => 'bg-info', 'empty_state_icon' => 'ti-test-pipe'],
        'radiology' => ['accent_class' => 'info', 'soft_bg_class' => 'info-subtle', 'icon' => 'ti-scan', 'hero_icon' => 'ti-scan', 'chart_accent' => '#0dcaf0', 'badge_class' => 'bg-info', 'empty_state_icon' => 'ti-scan'],
        'procedure' => ['accent_class' => 'warning', 'soft_bg_class' => 'warning-subtle', 'icon' => 'ti-tools', 'hero_icon' => 'ti-tools', 'chart_accent' => '#fd7e14', 'badge_class' => 'bg-warning text-dark', 'empty_state_icon' => 'ti-tools'],
        'theatre' => ['accent_class' => 'primary', 'soft_bg_class' => 'primary-subtle', 'icon' => 'ti-surgical-mask', 'hero_icon' => 'ti-surgical-mask', 'chart_accent' => '#6f42c1', 'badge_class' => 'bg-primary', 'empty_state_icon' => 'ti-surgical-mask'],
        'treatment' => ['accent_class' => 'success', 'soft_bg_class' => 'success-subtle', 'icon' => 'ti-first-aid-kit', 'hero_icon' => 'ti-first-aid-kit', 'chart_accent' => '#20c997', 'badge_class' => 'bg-success', 'empty_state_icon' => 'ti-first-aid-kit'],
        'nursing' => ['accent_class' => 'success', 'soft_bg_class' => 'success-subtle', 'icon' => 'ti-nurse', 'hero_icon' => 'ti-nurse', 'chart_accent' => '#198754', 'badge_class' => 'bg-success', 'empty_state_icon' => 'ti-nurse'],
        'pharmacy' => ['accent_class' => 'success', 'soft_bg_class' => 'success-subtle', 'icon' => 'ti-pill', 'hero_icon' => 'ti-pill', 'chart_accent' => '#198754', 'badge_class' => 'bg-success', 'empty_state_icon' => 'ti-pill'],
        'inpatient' => ['accent_class' => 'secondary', 'soft_bg_class' => 'secondary-subtle', 'icon' => 'ti-bed', 'hero_icon' => 'ti-bed', 'chart_accent' => '#64748b', 'badge_class' => 'bg-secondary', 'empty_state_icon' => 'ti-bed'],
        'maternity' => ['accent_class' => 'danger', 'soft_bg_class' => 'danger-subtle', 'icon' => 'ti-baby-carriage', 'hero_icon' => 'ti-baby-carriage', 'chart_accent' => '#d63384', 'badge_class' => 'bg-danger', 'empty_state_icon' => 'ti-baby-carriage'],
        'blood_bank' => ['accent_class' => 'danger', 'soft_bg_class' => 'danger-subtle', 'icon' => 'ti-droplet', 'hero_icon' => 'ti-droplet', 'chart_accent' => '#dc3545', 'badge_class' => 'bg-danger', 'empty_state_icon' => 'ti-droplet'],
        'mortuary' => ['accent_class' => 'dark', 'soft_bg_class' => 'secondary-subtle', 'icon' => 'ti-building-warehouse', 'hero_icon' => 'ti-building-warehouse', 'chart_accent' => '#343a40', 'badge_class' => 'bg-dark', 'empty_state_icon' => 'ti-building-warehouse'],
        'ambulance' => ['accent_class' => 'danger', 'soft_bg_class' => 'danger-subtle', 'icon' => 'ti-ambulance', 'hero_icon' => 'ti-ambulance', 'chart_accent' => '#fd7e14', 'badge_class' => 'bg-danger', 'empty_state_icon' => 'ti-ambulance'],
        'records' => ['accent_class' => 'secondary', 'soft_bg_class' => 'secondary-subtle', 'icon' => 'ti-folder', 'hero_icon' => 'ti-folder', 'chart_accent' => '#6c757d', 'badge_class' => 'bg-secondary', 'empty_state_icon' => 'ti-folder'],
        'finance' => ['accent_class' => 'success', 'soft_bg_class' => 'success-subtle', 'icon' => 'ti-cash-banknote', 'hero_icon' => 'ti-cash-banknote', 'chart_accent' => '#198754', 'badge_class' => 'bg-success', 'empty_state_icon' => 'ti-cash-banknote'],
        'stores' => ['accent_class' => 'warning', 'soft_bg_class' => 'warning-subtle', 'icon' => 'ti-packages', 'hero_icon' => 'ti-packages', 'chart_accent' => '#ffc107', 'badge_class' => 'bg-warning text-dark', 'empty_state_icon' => 'ti-packages'],
        'support' => ['accent_class' => 'dark', 'soft_bg_class' => 'secondary-subtle', 'icon' => 'ti-tool', 'hero_icon' => 'ti-tool', 'chart_accent' => '#212529', 'badge_class' => 'bg-dark', 'empty_state_icon' => 'ti-tool'],
        'administrative' => ['accent_class' => 'primary', 'soft_bg_class' => 'primary-subtle', 'icon' => 'ti-settings', 'hero_icon' => 'ti-settings', 'chart_accent' => '#0b5ed7', 'badge_class' => 'bg-primary', 'empty_state_icon' => 'ti-settings'],
        'generic' => ['accent_class' => 'secondary', 'soft_bg_class' => 'secondary-subtle', 'icon' => 'ti-layout-dashboard', 'hero_icon' => 'ti-layout-dashboard', 'chart_accent' => '#6c757d', 'badge_class' => 'bg-secondary', 'empty_state_icon' => 'ti-layout-dashboard'],
    ];

    public function for(?DepartmentType $type, ?string $dashboardKey = null): array
    {
        $key = $type?->value ?? $dashboardKey ?? 'generic';
        $theme = self::THEMES[$key] ?? self::THEMES['generic'];
        $theme['key'] = $key;

        return $theme;
    }

    public function all(): array
    {
        return self::THEMES;
    }

    public function hasThemeForEveryDepartmentType(): bool
    {
        foreach (DepartmentType::cases() as $type) {
            if (! isset(self::THEMES[$type->value])) {
                return false;
            }
        }

        return true;
    }
}
