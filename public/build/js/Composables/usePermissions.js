import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Permission / role / module composable.
 *
 * Backed by `auth.permissions`, `auth.roles`, `auth.modules` shared from
 * `App\Http\Middleware\HandleInertiaRequests`.
 *
 * Usage:
 *
 *   import { usePermissions } from '@/Composables/usePermissions';
 *
 *   const { can, hasRole, moduleEnabled, isSuperAdmin } = usePermissions();
 *   if (can('patients.delete')) { ... }
 */
export function usePermissions() {
    const page = usePage();

    const permissions = computed(() => page.props?.auth?.permissions ?? []);
    const roles       = computed(() => page.props?.auth?.roles ?? []);
    const modules     = computed(() => page.props?.auth?.modules ?? []);

    const isSuperAdmin = computed(
        () => roles.value.includes('Super Admin') || roles.value.includes('Admin')
    );

    /**
     * @param {string|string[]} perm — single name or array (any-of).
     */
    function can(perm) {
        if (isSuperAdmin.value) return true;
        if (Array.isArray(perm)) return perm.some(p => permissions.value.includes(p));
        return permissions.value.includes(perm);
    }

    /** Require *all* of the listed permissions. */
    function canAll(perms) {
        if (isSuperAdmin.value) return true;
        return (perms || []).every(p => permissions.value.includes(p));
    }

    function hasRole(role) {
        if (Array.isArray(role)) return role.some(r => roles.value.includes(r));
        return roles.value.includes(role);
    }

    function moduleEnabled(slug) {
        return modules.value.includes(slug);
    }

    return {
        permissions,
        roles,
        modules,
        isSuperAdmin,
        can,
        canAll,
        hasRole,
        moduleEnabled,
    };
}
