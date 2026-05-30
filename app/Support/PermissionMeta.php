<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Resolves metadata (module, description, risk) for permission names
 * using the rules in `config/permissions.php`.
 *
 * Pure / static — safe to call from anywhere (Inertia share, Blade,
 * Artisan commands, controllers).
 */
class PermissionMeta
{
    /**
     * Return ['name','module','description','risk'] for a permission.
     */
    public static function for(string $permission): array
    {
        return [
            'name'        => $permission,
            'module'      => self::module($permission),
            'description' => self::description($permission),
            'risk'        => self::risk($permission),
        ];
    }

    public static function module(string $permission): string
    {
        $overrides = (array) config('permissions.module_overrides', []);
        if (isset($overrides[$permission])) {
            return $overrides[$permission];
        }
        return Str::before($permission, '.') ?: 'misc';
    }

    public static function risk(string $permission): string
    {
        $overrides = (array) config('permissions.risk_overrides', []);
        if (isset($overrides[$permission])) {
            return $overrides[$permission];
        }

        foreach ((array) config('permissions.risk_rules', []) as $pattern => $level) {
            if (@preg_match($pattern, $permission)) {
                return $level;
            }
        }

        return 'NORMAL';
    }

    public static function description(string $permission): string
    {
        $explicit = (array) config('permissions.descriptions', []);
        if (isset($explicit[$permission])) {
            return $explicit[$permission];
        }

        // Derive from name: "pharmacy.dispense.create" -> "Create pharmacy dispense"
        $parts = explode('.', $permission);
        $verb  = array_pop($parts);
        $noun  = implode(' ', $parts);

        $verbMap = [
            'view'       => 'View',
            'list'       => 'List',
            'create'     => 'Create',
            'edit'       => 'Edit',
            'update'     => 'Update',
            'delete'     => 'Delete',
            'manage'     => 'Manage',
            'approve'    => 'Approve',
            'cancel'     => 'Cancel',
            'execute'    => 'Execute',
            'transition' => 'Change status of',
            'assign'     => 'Assign',
            'request'    => 'Request',
            'activate'   => 'Activate',
            'complete'   => 'Complete',
            'dispense'   => 'Dispense',
            'administer' => 'Administer',
            'discharge'  => 'Discharge',
            'transfer'   => 'Transfer',
            'submit'     => 'Submit',
            'access'     => 'Access',
        ];
        $verbLabel = $verbMap[$verb] ?? Str::title(str_replace('_', ' ', $verb));
        $nounLabel = $noun !== '' ? str_replace('_', ' ', $noun) : 'item';

        return trim("{$verbLabel} {$nounLabel}");
    }
}
