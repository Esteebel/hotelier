<script setup>
import { useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import { computed, watch } from "vue";

const props = defineProps({
    reservations: Array,
    rooms: Array,
    guests: Array,
    activeBranch: Object,
});

const form = useForm({
    reservation_id: "",
    room_id: "",
    guest_id: "",
    notes: "",
});

const selectedReservation = computed(() => {
    return props.reservations.find((r) => r.id === Number(form.reservation_id));
});

const availableRooms = computed(() => {
    if (!selectedReservation.value?.room_id) {
        return props.rooms;
    }
    return props.rooms.filter(
        (room) => room.id === selectedReservation.value.room_id,
    );
});

watch(
    () => form.reservation_id,
    () => {
        form.room_id = selectedReservation.value?.room_id ?? "";
        form.guest_id = selectedReservation.value?.guest_id ?? "";
    },
);

function store() {
    form.post(route("checkins.store"));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                New Check-in
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form
                    @submit.prevent="store"
                    class="rounded-lg bg-white p-8 shadow-sm"
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <InputLabel
                                for="reservation_id"
                                value="Reservation"
                            />
                            <select
                                id="reservation_id"
                                v-model="form.reservation_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">
                                    Select confirmed reservation...
                                </option>
                                <option
                                    v-for="reservation in reservations"
                                    :key="reservation.id"
                                    :value="reservation.id"
                                >
                                    {{ reservation.reservation_number }}
                                    - Check-in:
                                    {{ reservation.check_in }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.reservation_id"
                            />
                        </div>

                        <div>
                            <InputLabel for="room_id" value="Room" />
                            <select
                                id="room_id"
                                v-model="form.room_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select room...</option>
                                <option
                                    v-for="room in availableRooms"
                                    :key="room.id"
                                    :value="room.id"
                                >
                                    {{ room.room_number }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.room_id"
                            />
                        </div>

                        <div>
                            <InputLabel for="guest_id" value="Guest" />
                            <select
                                id="guest_id"
                                v-model="form.guest_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select guest...</option>
                                <option
                                    v-for="guest in guests"
                                    :key="guest.id"
                                    :value="guest.id"
                                >
                                    {{ guest.first_name }}
                                    {{ guest.last_name }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.guest_id"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="notes" value="Notes" />
                            <TextAreaInput
                                id="notes"
                                v-model="form.notes"
                                class="mt-1 block w-full"
                                rows="3"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.notes"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-4">
                        <PrimaryButton :disabled="form.processing">
                            Complete Check-in
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
