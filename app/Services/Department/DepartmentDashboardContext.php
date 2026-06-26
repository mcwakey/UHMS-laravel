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
            'is_preview' => $this->is_preview,
            'requested_dashboard_key' => $this->requested_dashboard_key,
        ];
    }
}
