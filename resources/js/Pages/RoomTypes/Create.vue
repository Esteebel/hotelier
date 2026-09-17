<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import RoomTypeForm from "@/Pages/RoomTypes/Partials/RoomTypeForm.vue";

const props = defineProps({
    activeBranch: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Add Room Type" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Add Room Type
                </h2>
                <p class="text-sm text-gray-500">
                    Add a room category for
                    {{
                        activeBranch
                            ? `${activeBranch.name} (${activeBranch.code})`
                            : "this branch"
                    }}.
                </p>
            </div>
        </template>

        <RoomTypeForm />
    </AuthenticatedLayout>
</template>
