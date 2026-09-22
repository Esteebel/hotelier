<script setup>
import { useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    reservations: Array,
    rooms: Array,
    activeBranch: Object,
});

const form = useForm({
    reservation_id: "",
    room_id: "",
    assigned_to: "",
    due_date: "",
    reason: "",
    notes: "",
    status: "pending",
});

function store() {
    form.post(route("room-tasks.store"));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                New Room Transfer
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
                                <option value="">Select reservation…</option>
                                <option
                                    v-for="reservation in reservations"
                                    :key="reservation.id"
                                    :value="reservation.id"
                                >
                                    {{ reservation.reservation_number }}
                                    ({{ reservation.room?.room_number }})
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.reservation_id"
                            />
                        </div>

                        <div>
                            <InputLabel for="room_id" value="From Room" />
                            <select
                                id="room_id"
                                v-model="form.room_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select room…</option>
                                <option
                                    v-for="room in rooms"
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
                            <InputLabel for="assigned_to" value="To Room" />
                            <select
                                id="assigned_to"
                                v-model="form.assigned_to"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select room…</option>
                                <option
                                    v-for="room in rooms"
                                    :key="room.id"
                                    :value="room.id"
                                >
                                    {{ room.room_number }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.assigned_to"
                            />
                        </div>

                        <div>
                            <InputLabel for="due_date" value="Transfer Date" />
                            <TextInput
                                id="due_date"
                                v-model="form.due_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.due_date"
                            />
                        </div>

                        <div>
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.status"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="reason" value="Reason" />
                            <TextInput
                                id="reason"
                                v-model="form.reason"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.reason"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="notes" value="Notes" />
                            <TextAreaInputInput
                                id="notes"
                                v-model="form.notes"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.notes"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-4">
                        <PrimaryButton :disabled="form.processing">
                            Save Transfer
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
