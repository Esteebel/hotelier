<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import RoomTypeForm from "@/Pages/RoomTypes/Partials/RoomTypeForm.vue";

const props = defineProps({
    roomType: {
        type: Object,
        required: true,
    },
    activeBranch: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head :title="`Edit ${roomType.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit Room Type
                </h2>
                <p class="text-sm text-gray-500">{{ roomType.name }}</p>
            </div>
        </template>

        <RoomTypeForm :room-type="roomType" />
    </AuthenticatedLayout>
</template>
