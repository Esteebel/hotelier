<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import RoomForm from "@/Pages/Rooms/Partials/RoomForm.vue";

defineProps({
    room: {
        type: Object,
        required: true,
    },
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
    <Head :title="`Edit Room ${room.room_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit Room
                </h2>
                <p class="text-sm text-gray-500">
                    {{ room.room_number }} —
                    {{ room.room_type?.name ?? "No type" }}
                </p>
            </div>
        </template>

        <RoomForm :room="room" :room-types="roomTypes" />
    </AuthenticatedLayout>
</template>
