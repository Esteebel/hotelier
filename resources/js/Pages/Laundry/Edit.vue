<script setup>
import { useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    order: Object,
    rooms: Array,
    guests: Array,
    activeBranch: Object,
});
const form = useForm({
    room_id: props.order.room_id ?? "",
    guest_id: props.order.guest_id ?? "",
    items:
        typeof props.order.items === "string"
            ? props.order.items
            : JSON.stringify(props.order.items ?? [], null, 2),
    total_amount: props.order.total_amount ?? "",
    pickup_date: props.order.pickup_date ?? "",
    delivery_date: props.order.delivery_date ?? "",
    status: props.order.status ?? "pending",
    notes: props.order.notes ?? "",
});
function update() {
    form.put(route("laundry.update", props.order.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header
            ><h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit {{ order.order_number }}
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
                            <InputLabel for="pickup_date" value="Pickup Date" />
                            <TextInput
                                id="pickup_date"
                                v-model="form.pickup_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.pickup_date"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="delivery_date"
                                value="Delivery Date"
                            />
                            <TextInput
                                id="delivery_date"
                                v-model="form.delivery_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.delivery_date"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="total_amount"
                                value="Total Amount"
                            />
                            <TextInput
                                id="total_amount"
                                v-model="form.total_amount"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                                placeholder="0.00"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.total_amount"
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
                                <option value="processing">Processing</option>
                                <option value="picked_up">Picked Up</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.status"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="items" value="Items (JSON)" />
                            <TextAreaInput
                                id="items"
                                v-model="form.items"
                                class="mt-1 block w-full"
                                rows="4"
                                placeholder='[{"name": "Shirt", "quantity": 2, "service": "wash"}]'
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.items"
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
                            >Update Order</PrimaryButton
                        >
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
