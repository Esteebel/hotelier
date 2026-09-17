<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import UserForm from "@/Pages/Users/Partials/UserForm.vue";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
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
    <Head :title="`Edit ${user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit User
                </h2>
                <p class="text-sm text-gray-500">{{ user.email }}</p>
            </div>
        </template>

        <UserForm :user="user" :roles="roles" :branches="branches" />
    </AuthenticatedLayout>
</template>
