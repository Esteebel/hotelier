<script setup>
import { useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";

const props = defineProps({
    order: Object,
    rooms: Array,
    guests: Array,
    activeBranch: Object,
});
const form = useForm({
    room_id: props.order.room_id ?? "",
    guest_id: props.order.guest_id ?? "",
    service_type: props.order.service_type ?? "room_service",
    description: props.order.description ?? "",
    priority: props.order.priority ?? "normal",
    status: props.order.status ?? "pending",
    notes: props.order.notes ?? "",
});
function update() {
    form.put(route("services.update", props.order.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header
            ><h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit {{ order.service_number }}
            </h2></template
        >
        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form
                    @submit.prevent="update"
                    class="rounded-lg bg-white p-8 shadow-sm"
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="room_id" value="Room" />
                            <select
                                id="room_id"
                                v-model="form.room_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select room...</option>
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
                                    {{ guest.first_name }} {{ guest.last_name }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.guest_id"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="service_type"
                                value="Service Type"
                            />
                            <select
                                id="service_type"
                                v-model="form.service_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="room_service">
                                    Room Service
                                </option>
                                <option value="concierge">Concierge</option>
                                <option value="transportation">
                                    Transportation
                                </option>
                                <option value="spa">Spa</option>
                                <option value="tours">Tours</option>
                                <option value="other">Other</option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.service_type"
                            />
                        </div>
                        <div>
                            <InputLabel for="priority" value="Priority" />
                            <select
                                id="priority"
                                v-model="form.priority"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.priority"
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
                                <option value="in-progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.status"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="description" value="Description" />
                            <TextAreaInput
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full"
                                rows="3"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.description"
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
                        <PrimaryButton :disabled="form.processing"
                            >Update Service</PrimaryButton
                        >
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
