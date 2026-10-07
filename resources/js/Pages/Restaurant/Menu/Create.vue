<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import MenuForm from "@/Pages/Restaurant/Menu/Partials/MenuForm.vue";

defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    activeBranch: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Add Menu Item" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Add Menu Item
                </h2>
                <p class="text-sm text-gray-500">
                    Add a food or drink to the restaurant menu
                    <template v-if="activeBranch">
                        · {{ activeBranch.name }}
                    </template>
                    .
                </p>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <MenuForm :categories="categories" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
