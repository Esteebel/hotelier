<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import CustomerLayout from "@/Layouts/CustomerLayout.vue";
import { formatMoney } from "@/Utils/format.js";

const props = defineProps({
    guest: Object,
    stats: Object,
    activeReservation: Object,
});

const cards = [
    {
        key: "upcoming_reservations",
        label: "Upcoming Stays",
        icon: "calendar",
        href: route("portal.reservations"),
    },
    {
        key: "pending_orders",
        label: "Active Orders",
        icon: "inbox",
        href: route("portal.orders"),
    },
    {
        key: "total_stays",
        label: "Total Reservations",
        icon: "bed",
        href: route("portal.reservations"),
    },
    {
        key: "balance_due",
        label: "Balance Due",
        icon: "banknote",
        href: route("portal.bills"),
        format: "money",
    },
];
</script>

<template>
    <Head title="Guest Portal" />

    <CustomerLayout>
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-gray-900">Overview</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Everything about your stay, in one place. Tap any card to
                    see the details.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="card in cards"
                    :key="card.key"
                    :href="card.href"
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-lg"
                            :class="
                                card.format === 'money'
                                    ? 'bg-amber-50 text-amber-600'
                                    : 'bg-indigo-50 text-indigo-600'
                            "
                        >
                            <AppIcon
                                :name="card.icon"
                                classes="[&>svg]:h-5 [&>svg]:w-5"
                            />
                        </span>
                        <div>
                            <p class="text-sm text-gray-500">
                                {{ card.label }}
                            </p>
                            <p class="text-xl font-semibold text-gray-900">
                                {{
                                    card.format === "money"
                                        ? formatMoney(stats[card.key])
                                        : stats[card.key]
                                }}
                            </p>
                        </div>
                    </div>
                </Link>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2"
                >
                    <h2 class="text-base font-semibold text-gray-900">
                        Current / Next Stay
                    </h2>

                    <div
                        v-if="activeReservation"
                        class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-5"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <p class="text-sm font-medium text-indigo-600">
                                    {{ activeReservation.reservation_number }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600">
                                    Room
                                    {{
                                        activeReservation.room?.room_number ??
                                        "—"
                                    }}
                                </p>
                            </div>
                            <span
                                class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700"
                            >
                                {{ activeReservation.status_label }}
                            </span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Check-in</p>
                                <p class="font-medium text-gray-900">
                                    {{ activeReservation.check_in }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500">Check-out</p>
                                <p class="font-medium text-gray-900">
                                    {{ activeReservation.check_out }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <p
                        v-else
                        class="mt-4 rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500"
                    >
                        No upcoming stays.
                        <Link
                            :href="route('portal.rooms')"
                            class="font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Browse available rooms
                        </Link>
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h2 class="text-base font-semibold text-gray-900">
                        Quick Actions
                    </h2>
                    <div class="mt-4 space-y-3">
                        <Link
                            :href="route('portal.rooms')"
                            class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50"
                        >
                            <AppIcon
                                name="bed"
                                classes="[&>svg]:h-4 [&>svg]:w-4 text-indigo-600"
                            />
                            Browse rooms & book
                        </Link>
                        <Link
                            :href="route('portal.menu')"
                            class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50"
                        >
                            <AppIcon
                                name="inbox"
                                classes="[&>svg]:h-4 [&>svg]:w-4 text-indigo-600"
                            />
                            Order from the menu
                        </Link>
                        <Link
                            :href="route('portal.orders')"
                            class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50"
                        >
                            <AppIcon
                                name="receipt"
                                classes="[&>svg]:h-4 [&>svg]:w-4 text-indigo-600"
                            />
                            Track my orders
                        </Link>
                        <Link
                            :href="route('portal.bills')"
                            class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50"
                        >
                            <AppIcon
                                name="card"
                                classes="[&>svg]:h-4 [&>svg]:w-4 text-indigo-600"
                            />
                            View bills & payments
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
