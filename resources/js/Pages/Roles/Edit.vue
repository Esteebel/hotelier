<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import RoleForm from "@/Pages/Roles/Partials/RoleForm.vue";

defineProps({
    role: {
        type: Object,
        required: true,
    },
    permissionGroups: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head :title="`Edit ${role.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit Role
                </h2>
                <p class="text-sm text-gray-500">{{ role.name }}</p>
            </div>
        </template>

        <RoleForm :role="role" :permission-groups="permissionGroups" />
    </AuthenticatedLayout>
</template>
