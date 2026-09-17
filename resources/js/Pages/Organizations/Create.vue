<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import OrganizationForm from "@/Pages/Organizations/Partials/OrganizationForm.vue";
</script>

<template>
    <Head title="Add Organization" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Add Organization
                </h2>
                <p class="text-sm text-gray-500">
                    Register a new parent company.
                </p>
            </div>
        </template>

        <OrganizationForm />
    </AuthenticatedLayout>
</template>
