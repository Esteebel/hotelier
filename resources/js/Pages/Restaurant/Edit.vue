<script setup>
import { useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import MenuPicker from "@/Components/MenuPicker.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";

const props = defineProps({
    order: Object,
    rooms: Array,
    guests: Array,
    menuItems: {
        type: Array,
        default: () => [],
    },
    activeBranch: Object,
});

const toSelection = (orderItems) =>
    (Array.isArray(orderItems) ? orderItems : [])
        .filter((row) => row.menu_item_id)
        .map((row) => ({
            menu_item_id: row.menu_item_id,
            quantity: row.quantity,
        }));

const form = useForm({
    room_id: props.order.room_id ?? "",
    guest_id: props.order.guest_id ?? "",
    order_type: props.order.order_type ?? "dine_in",
    items: toSelection(props.order.items),
    status: props.order.status ?? "pending",
    notes: props.order.notes ?? "",
});
function update() {
    form.put(route("restaurant.update", props.order.id));
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
                <form @submit.prevent="update" class="space-y-6">
                    <div class="rounded-lg bg-white p-8 shadow-sm">
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
                                        {{ guest.first_name }}
                                        {{ guest.last_name }}
                                    </option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.guest_id"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    for="order_type"
                                    value="Order Type"
                                />
                                <select
                                    id="order_type"
                                    v-model="form.order_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="dine_in">Dine In</option>
                                    <option value="takeout">Takeout</option>
                                    <option value="delivery">Delivery</option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.order_type"
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
                                    <option value="in-progress">
                                        In Progress
                                    </option>
                                    <option value="ready">Ready</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.status"
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <InputLabel for="notes" value="Notes" />
                                <TextAreaInput
                                    id="notes"
                                    v-model="form.notes"
                                    class="mt-1 block w-full"
                                    rows="2"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.notes"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-8 shadow-sm">
                        <InputLabel value="Menu" />
                        <p class="mb-4 mt-0.5 text-sm text-gray-500">
                            Choose items from the hotel menu. The total is
                            calculated automatically.
                        </p>
                        <MenuPicker
                            v-model="form.items"
                            :menu-items="menuItems"
                        />
                        <InputError class="mt-2" :message="form.errors.items" />
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <PrimaryButton :disabled="form.processing"
                            >Update Order</PrimaryButton
                        >
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
