<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import RoomForm from "@/Pages/Rooms/Partials/RoomForm.vue";

defineProps({
    roomTypes: {
        type: Array,
        required: true,
    },
    activeBranch: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Add Room" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Add Room
                </h2>
                <p class="text-sm text-gray-500">
                    Register a new room for
                    {{
                        activeBranch ? activeBranch.name : "your active branch"
                    }}.
                </p>
            </div>
        </template>

        <RoomForm :room-types="roomTypes" />
    </AuthenticatedLayout>
</template>
