import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

export function usePermissions() {
    const user = computed(() => usePage().props.value.auth?.user);
    const roles = computed(() => user.value?.roles ?? []);
    const permissions = computed(() => user.value?.permissions ?? []);

    function hasRole(role) {
        return roles.value.includes(role);
    }

    function hasPermission(permission) {
        return permissions.value.includes(permission);
    }

    function isAdmin() {
        return hasRole("admin") || hasRole("super_admin");
    }

    function isManager() {
        return hasRole("manager") || isAdmin();
    }

    return { user, roles, permissions, hasRole, hasPermission, isAdmin, isManager };
}
