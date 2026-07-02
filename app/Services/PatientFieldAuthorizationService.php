<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;

class PatientFieldAuthorizationService
{
    public function canViewField(string $field, ?Authenticatable $user = null): bool
    {
        $definition = $this->definition($field);

        if (! $definition) {
            return false;
        }

        $level = (int) ($definition['level'] ?? 0);
        if ($level <= 1) {
            return true;
        }

        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        $fieldPermission = $definition['permission'] ?? null;
        if ($fieldPermission && $user->can($fieldPermission)) {
            return true;
        }

        $aggregatePermission = config("patient_privacy.aggregate_permissions.$level");

        return $aggregatePermission ? $user->can($aggregatePermission) : false;
    }

    public function canEditField(string $field, ?Authenticatable $user = null): bool
    {
        $definition = $this->definition($field);

        if (! $definition) {
            return false;
        }

        $level = (int) ($definition['level'] ?? 0);
        if ($level <= 1) {
            return true;
        }

        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        $fieldPermission = $definition['edit_permission'] ?? null;
        if ($fieldPermission && $user->can($fieldPermission)) {
            return true;
        }

        $aggregatePermission = config("patient_privacy.aggregate_edit_permissions.$level");

        return $aggregatePermission ? $user->can($aggregatePermission) : false;
    }

    public function canCaptureOnCreate(string $field, ?Authenticatable $user = null): bool
    {
        $definition = $this->definition($field);

        if (! $definition) {
            return false;
        }

        $level = (int) ($definition['level'] ?? 0);
        if ($level <= 1) {
            return true;
        }

        if (($definition['create_capture'] ?? false) && $user && method_exists($user, 'can') && $user->can('patients.create')) {
            return true;
        }

        return $this->canEditField($field, $user);
    }

    public function definition(string $field): ?array
    {
        $fields = config('patient_privacy.fields', []);

        return $fields[$field] ?? null;
    }

    public function level(string $field): int
    {
        return (int) ($this->definition($field)['level'] ?? 0);
    }

    public function permissionFor(string $field): ?string
    {
        return $this->definition($field)['permission'] ?? null;
    }

    public function editPermissionFor(string $field): ?string
    {
        return $this->definition($field)['edit_permission'] ?? null;
    }
}
