<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import OrganizationForm from "@/Pages/Organizations/Partials/OrganizationForm.vue";

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="`Edit ${organization.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit Organization
                </h2>
                <p class="text-sm text-gray-500">
                    {{ organization.name }}
                </p>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <OrganizationForm :organization="organization" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
