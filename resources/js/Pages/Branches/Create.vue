<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BranchForm from "@/Pages/Branches/Partials/BranchForm.vue";

const props = defineProps({
    organizations: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Add Branch" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Add Branch
                </h2>
                <p class="text-sm text-gray-500">
                    Register a new branch property.
                </p>
            </div>
        </template>

        <BranchForm :organizations="organizations" />
    </AuthenticatedLayout>
</template>
