<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import RoleForm from "@/Pages/Roles/Partials/RoleForm.vue";

defineProps({
    permissionGroups: {
        type: Array,
        default: () => [],
    },
    hidden_slug: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <Head title="Add Role" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Add Role
                </h2>
                <p class="text-sm text-gray-500">
                    Create a new role and choose the permissions it grants.
                </p>
            </div>
        </template>

        <RoleForm :permission-groups="permissionGroups" />
    </AuthenticatedLayout>
</template>
