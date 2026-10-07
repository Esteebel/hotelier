<script setup>
import { Head } from "@inertiajs/vue3";
import { useForm } from "@inertiajs/vue3";
import CustomerLayout from "@/Layouts/CustomerLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import MenuPicker from "@/Components/MenuPicker.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";

const props = defineProps({
    guest: Object,
    menuItems: Array,
    rooms: Array,
});

const form = useForm({
    order_type: "dine_in",
    room_id: "",
    items: [],
    notes: "",
});

function submit() {
    form.post(route("portal.menu.orders.store"), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Menu" />

    <CustomerLayout>
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">
                    Restaurant Menu
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Choose from our available dishes and drinks. Your order is
                    sent straight to the kitchen.
                </p>
            </div>

            <div
                v-if="$page.props.flash?.success"
                class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            >
                {{ $page.props.flash.success }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Order Type" />
                            <select
                                v-model="form.order_type"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                        <div v-if="rooms.length">
                            <InputLabel value="Deliver to Room (optional)" />
                            <select
                                v-model="form.room_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select room…</option>
                                <option
                                    v-for="room in rooms"
                                    :key="room.id"
                                    :value="room.id"
                                >
                                    Room {{ room.room_number }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.room_id"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <MenuPicker v-model="form.items" :menu-items="menuItems" />
                    <InputError class="mt-2" :message="form.errors.items" />
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <InputLabel value="Notes (allergies, preferences…)" />
                    <TextAreaInput
                        v-model="form.notes"
                        class="mt-1 block w-full"
                        rows="2"
                    />
                    <InputError class="mt-2" :message="form.errors.notes" />
                </div>

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing">
                        {{ form.processing ? "Placing order…" : "Place Order" }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </CustomerLayout>
</template>
