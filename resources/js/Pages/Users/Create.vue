<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import UserForm from "@/Pages/Users/Partials/UserForm.vue";

const props = defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
    branches: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Add User" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Add User
                </h2>
                <p class="text-sm text-gray-500">
                    Create a new system user account.
                </p>
            </div>
        </template>

        <UserForm :roles="roles" :branches="branches" />
    </AuthenticatedLayout>
</template>
