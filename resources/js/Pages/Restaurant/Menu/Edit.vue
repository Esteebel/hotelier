<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import MenuForm from "@/Pages/Restaurant/Menu/Partials/MenuForm.vue";

defineProps({
    menuItem: {
        type: Object,
        required: true,
    },
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
    <Head :title="`Edit ${menuItem.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit Menu Item
                </h2>
                <p class="text-sm text-gray-500">
                    {{ menuItem.name }} · {{ menuItem.category }}
                </p>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <MenuForm :menu-item="menuItem" :categories="categories" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
