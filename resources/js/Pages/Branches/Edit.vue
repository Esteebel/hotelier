<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BranchForm from "@/Pages/Branches/Partials/BranchForm.vue";

const props = defineProps({
    branch: {
        type: Object,
        required: true,
    },
    organizations: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head :title="`Edit ${branch.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit Branch
                </h2>
                <p class="text-sm text-gray-500">{{ branch.name }}</p>
            </div>
        </template>

        <BranchForm :branch="branch" :organizations="organizations" />
    </AuthenticatedLayout>
</template>
