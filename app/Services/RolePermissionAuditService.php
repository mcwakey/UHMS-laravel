<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Support\PermissionMeta;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

/**
 * Security audit funnel for role + permission mutations.
 *
 * This is a LOGGING funnel only — it never mutates roles/permissions. Callers
 * (RoleController, UserPermissionController) keep their business logic and hand
 * this service the before/after state; it computes the diff, flags CRITICAL
 * permissions (via PermissionMeta risk levels), and writes structured events to
 * the central ActivityLogService under the ROLES / PERMISSIONS modules.
 *
 * These are SYSTEM/SECURITY events: they must surface in the global/security log
 * and must NEVER carry patient/visit context.
 */
class RolePermissionAuditService
{
    public function __construct(private ActivityLogService $log) {}

    public function roleCreated(Role $role): void
    {
        $this->safe(fn () => $this->log->log(
            LogModule::ROLES,
            'ROLE_CREATED',
            [
                'role_id' => $role->getKey(),
                'severity' => LogSeverity::INFO,
                'new_values' => $this->roleAttributes($role),
                'metadata' => ['role_name' => $role->name],
                'source_type' => 'role',
                'source_id' => $role->getKey(),
            ],
            $role,
            'Role created: ' . $role->name,
        ));
    }

    /**
     * @param  array<string,mixed>  $old
     * @param  array<string,mixed>  $new
     */
    public function roleUpdated(Role $role, array $old, array $new): void
    {
        if ($old == $new) {
            return;
        }

        $this->safe(fn () => $this->log->log(
            LogModule::ROLES,
            'ROLE_UPDATED',
            [
                'role_id' => $role->getKey(),
                'severity' => LogSeverity::WARNING,
                'old_values' => $old,
                'new_values' => $new,
                'metadata' => ['role_name' => $role->name],
                'source_type' => 'role',
                'source_id' => $role->getKey(),
            ],
            $role,
            'Role updated: ' . $role->name,
        ));
    }

    public function roleDeleted(Role $role): void
    {
        $this->safe(fn () => $this->log->log(
            LogModule::ROLES,
            'ROLE_DELETED',
            [
                'role_id' => $role->getKey(),
                'severity' => LogSeverity::WARNING,
                'old_values' => $this->roleAttributes($role),
                'metadata' => ['role_name' => $role->name],
                'source_type' => 'role',
                'source_id' => $role->getKey(),
            ],
            $role,
            'Role deleted: ' . $role->name,
        ));
    }

    /**
     * Log a role's permission set change (Spatie syncPermissions).
     *
     * @param  array<int,string>  $before  permission names before sync
     * @param  array<int,string>  $after   permission names after sync
     */
    public function rolePermissionsUpdated(Role $role, array $before, array $after): void
    {
        $this->logPermissionSync(
            subject: $role,
            module: LogModule::PERMISSIONS,
            event: 'ROLE_PERMISSIONS_UPDATED',
            subjectLabel: 'role ' . $role->name,
            sourceType: 'role',
            roleId: $role->getKey(),
            before: $before,
            after: $after,
            description: 'Permissions updated for role: ' . $role->name,
        );
    }

    /**
     * Log a user's DIRECT permission overrides change (additive on top of roles).
     *
     * @param  array<int,string>  $before
     * @param  array<int,string>  $after
     */
    public function userPermissionsUpdated(Model $user, array $before, array $after, ?string $reason = null): void
    {
        $name = $user->name ?? ('#' . $user->getKey());
        $this->logPermissionSync(
            subject: $user,
            module: LogModule::PERMISSIONS,
            event: 'USER_PERMISSIONS_UPDATED',
            subjectLabel: 'user ' . $name,
            sourceType: 'user',
            roleId: null,
            before: $before,
            after: $after,
            description: 'Direct permissions updated for user: ' . $name,
            reason: $reason,
            targetUserId: $user->getKey(),
        );
    }

    /**
     * Shared writer for both role and user permission syncs.
     *
     * @param  array<int,string>  $before
     * @param  array<int,string>  $after
     */
    private function logPermissionSync(
        Model $subject,
        LogModule $module,
        string $event,
        string $subjectLabel,
        string $sourceType,
        ?int $roleId,
        array $before,
        array $after,
        string $description,
        ?string $reason = null,
        ?int $targetUserId = null,
    ): void {
        $before = array_values(array_unique($before));
        $after = array_values(array_unique($after));
        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        if ($added === [] && $removed === []) {
            return;
        }

        $criticalAdded = $this->criticalOnly($added);
        $criticalRemoved = $this->criticalOnly($removed);
        $hasCritical = $criticalAdded !== [] || $criticalRemoved !== [];

        $context = array_filter([
            'role_id' => $roleId,
            'target_user_id' => $targetUserId,
            'severity' => $hasCritical ? LogSeverity::CRITICAL : LogSeverity::WARNING,
            'old_values' => ['permissions' => $before],
            'new_values' => ['permissions' => $after],
            'reason' => $reason,
            'metadata' => [
                'added_permissions' => $added,
                'removed_permissions' => $removed,
                'critical_permissions_added' => $criticalAdded,
                'critical_permissions_removed' => $criticalRemoved,
                'permission_count' => count($after),
            ],
            'source_type' => $sourceType,
            'source_id' => $subject->getKey(),
        ], fn ($v) => $v !== null);

        $this->safe(fn () => $this->log->log($module, $event, $context, $subject, $description));

        if ($criticalAdded !== []) {
            $this->logCritical($subject, $module, 'CRITICAL_PERMISSION_ASSIGNED', $sourceType, $roleId, $targetUserId,
                $criticalAdded, 'Critical permission assigned to ' . $subjectLabel . ': ' . implode(', ', $criticalAdded), $reason);
        }
        if ($criticalRemoved !== []) {
            $this->logCritical($subject, $module, 'CRITICAL_PERMISSION_REMOVED', $sourceType, $roleId, $targetUserId,
                $criticalRemoved, 'Critical permission removed from ' . $subjectLabel . ': ' . implode(', ', $criticalRemoved), $reason);
        }
    }

    /** @param array<int,string> $permissions */
    private function logCritical(
        Model $subject,
        LogModule $module,
        string $event,
        string $sourceType,
        ?int $roleId,
        ?int $targetUserId,
        array $permissions,
        string $description,
        ?string $reason,
    ): void {
        $context = array_filter([
            'role_id' => $roleId,
            'target_user_id' => $targetUserId,
            'severity' => LogSeverity::CRITICAL,
            'reason' => $reason,
            'metadata' => ['critical_permissions' => $permissions],
            'source_type' => $sourceType,
            'source_id' => $subject->getKey(),
        ], fn ($v) => $v !== null);

        $this->safe(fn () => $this->log->log($module, $event, $context, $subject, $description));
    }

    /** @param array<int,string> $names @return array<int,string> */
    private function criticalOnly(array $names): array
    {
        return array_values(array_filter($names, fn ($n) => PermissionMeta::risk($n) === 'CRITICAL'));
    }

    /** @return array<string,mixed> */
    private function roleAttributes(Role $role): array
    {
        return [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
        ];
    }

    private function safe(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            // A security log must never break the role/permission action itself.
        }
    }
}
