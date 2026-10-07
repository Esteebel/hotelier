import { usePage } from "@inertiajs/vue3";

export function usePermissions() {
    const page = usePage();

    const permissions = () => page.props.auth?.permissions ?? [];
    const roles = () => page.props.auth?.roles ?? [];

    const hasPermission = (permission) => permissions().includes(permission);
    const hasRole = (role) => roles().includes(role);
    const isSuperAdmin = () => hasRole("super-admin");

    return { permissions, roles, hasPermission, hasRole, isSuperAdmin };
}
