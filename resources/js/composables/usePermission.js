import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermission() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user || null);
    const roles = computed(() => user.value?.roles || []);
    const permissions = computed(() => user.value?.permissions || []);

    const isSuperAdmin = computed(() => {
        if (!user.value) return false;
        const currentRoles = roles.value;
        return (
            currentRoles.includes('owner') ||
            currentRoles.includes('admin') ||
            currentRoles.includes('super-admin')
        );
    });

    const can = (permission) => {
        if (!user.value) return false;
        if (isSuperAdmin.value) return true;

        if (Array.isArray(permission)) {
            return permission.some((p) => permissions.value.includes(p));
        }

        return permissions.value.includes(permission);
    };

    const canAny = (perms = []) => {
        if (!user.value) return false;
        if (isSuperAdmin.value) return true;

        return perms.some((p) => permissions.value.includes(p));
    };

    const canAll = (perms = []) => {
        if (!user.value) return false;
        if (isSuperAdmin.value) return true;

        return perms.every((p) => permissions.value.includes(p));
    };

    const hasRole = (role) => {
        if (!user.value) return false;
        if (Array.isArray(role)) {
            return role.some((r) => roles.value.includes(r));
        }
        return roles.value.includes(role);
    };

    return {
        user,
        roles,
        permissions,
        isSuperAdmin,
        can,
        canAny,
        canAll,
        hasRole,
    };
}
