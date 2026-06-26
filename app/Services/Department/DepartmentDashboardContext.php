<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;

class DepartmentDashboardContext
{
    public function __construct(
        public readonly User $user,
        public readonly ?Department $department,
        public readonly ?int $department_id,
        public readonly ?DepartmentType $department_type,
        public readonly string $department_type_label,
        public readonly string $dashboard_key,
        public readonly string $menu_profile_key,
        public readonly array $theme,
        public readonly bool $is_global_context,
        public readonly bool $can_preview_departments,
        public readonly mixed $available_departments,
        public readonly ?Department $current_department,
        public readonly ?int $current_department_id,
        public readonly ?DepartmentType $current_department_type,
        public readonly bool $is_switched_context,
        public readonly bool $can_switch_department,
        public readonly bool $can_use_global_context,
        public readonly bool $is_preview = false,
        public readonly ?string $requested_dashboard_key = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user' => $this->user,
            'department' => $this->department,
            'department_id' => $this->department_id,
            'department_type' => $this->department_type,
            'department_type_label' => $this->department_type_label,
            'dashboard_key' => $this->dashboard_key,
            'menu_profile_key' => $this->menu_profile_key,
            'theme' => $this->theme,
            'is_global_context' => $this->is_global_context,
            'can_preview_departments' => $this->can_preview_departments,
            'available_departments' => $this->available_departments,
            'current_department' => $this->current_department,
            'current_department_id' => $this->current_department_id,
            'current_department_type' => $this->current_department_type,
            'is_switched_context' => $this->is_switched_context,
            'can_switch_department' => $this->can_switch_department,
            'can_use_global_context' => $this->can_use_global_context,
            'is_preview' => $this->is_preview,
            'requested_dashboard_key' => $this->requested_dashboard_key,
        ];
    }
}
