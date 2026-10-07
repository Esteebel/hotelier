<script setup>
import { Head } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import CustomerLayout from "@/Layouts/CustomerLayout.vue";

const props = defineProps({
    orders: Object,
});

const statusStyles = {
    pending: "bg-amber-100 text-amber-800",
    "in-progress": "bg-blue-100 text-blue-800",
    ready: "bg-emerald-100 text-emerald-700",
    completed: "bg-gray-100 text-gray-600",
    cancelled: "bg-rose-100 text-rose-700",
};

const formatMoney = (amount) => `₦${Number(amount ?? 0).toLocaleString()}`;
</script>

<template>
    <Head title="My Orders" />

    <CustomerLayout>
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">My Orders</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Food and drink orders you have placed.
                </p>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <ul v-if="orders.data.length" class="divide-y divide-gray-100">
                    <li
                        v-for="order in orders.data"
                        :key="order.id"
                        class="p-5"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <p
                                    class="text-sm font-semibold text-indigo-600"
                                >
                                    {{ order.order_number }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ order.order_type }} ·
                                    {{ order.created_at }}
                                </p>
                            </div>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="
                                    statusStyles[order.status] ??
                                    'bg-gray-100 text-gray-600'
                                "
                            >
                                {{ order.status }}
                            </span>
                        </div>

                        <ul class="mt-3 space-y-1">
                            <li
                                v-for="row in order.items ?? []"
                                :key="row.menu_item_id ?? row.name"
                                class="flex justify-between text-sm text-gray-600"
                            >
                                <span>{{ row.quantity }}× {{ row.name }}</span>
                                <span class="font-medium text-gray-900">
                                    {{ formatMoney(row.subtotal) }}
                                </span>
                            </li>
                        </ul>

                        <div
                            class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3"
                        >
                            <p v-if="order.notes" class="text-xs text-gray-500">
                                Note: {{ order.notes }}
                            </p>
                            <span></span>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ formatMoney(order.total_amount) }}
                            </p>
                        </div>
                    </li>
                </ul>

                <div v-else class="px-6 py-14 text-center">
                    <AppIcon
                        name="inbox"
                        classes="[&>svg]:h-10 [&>svg]:w-10 mx-auto text-gray-300"
                    />
                    <p class="mt-3 text-sm font-medium text-gray-900">
                        No orders yet
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        Head to the menu to place your first order.
                    </p>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
