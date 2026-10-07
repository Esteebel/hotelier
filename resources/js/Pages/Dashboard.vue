<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    stats: Object,
    recent_reservations: Array,
    room_type_breakdown: Array,
    activeBranch: Object,
    scopeLabel: String,
});

const today = new Date().toLocaleDateString("en-GB", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
});

const summaryCards = [
    {
        label: "Occupancy",
        value: `${props.stats.occupancy_rate}%`,
        detail: `${props.stats.occupied_rooms} of ${props.stats.total_rooms} rooms`,
        icon: "trendingUp",
        accent: "bg-indigo-100 text-indigo-700",
        href: route("rooms.index", { status: "occupied" }),
    },
    {
        label: "Today's Arrivals",
        value: props.stats.today_arrivals,
        detail: "expected check-ins",
        icon: "userRound",
        accent: "bg-emerald-100 text-emerald-700",
        href: route("reservations.index", { status: "confirmed" }),
    },
    {
        label: "Today's Departures",
        value: props.stats.today_departures,
        detail: "due to depart",
        icon: "door",
        accent: "bg-amber-100 text-amber-700",
        href: route("checkouts.index"),
    },
    {
        label: "Revenue Today",
        value: formatCurrency(props.stats.today_revenue),
        detail: "confirmed payments",
        icon: "banknote",
        accent: "bg-rose-100 text-rose-700",
        href: route("payments.index", { status: "completed" }),
    },
];

const secondaryCards = [
    {
        label: "Available Rooms",
        value: props.stats.available_rooms,
        icon: "bed",
        href: route("rooms.index", { status: "available" }),
    },
    {
        label: "Dirty Rooms",
        value: props.stats.dirty_rooms,
        icon: "cleaning",
        href: route("rooms.index", { status: "dirty" }),
    },
    {
        label: "Confirmed Reservations",
        value: props.stats.confirmed_reservations,
        icon: "calendar",
        href: route("reservations.index", { status: "confirmed" }),
    },
    {
        label: "Active Guests",
        value: props.stats.active_guests,
        icon: "users",
        href: route("reservations.index", { status: "checked_in" }),
    },
    {
        label: "Month Revenue",
        value: formatCurrency(props.stats.month_revenue),
        icon: "trendingUp",
        href: route("payments.index", { status: "completed" }),
    },
    {
        label: "Outstanding Invoices",
        value: formatCurrency(props.stats.outstanding_invoices),
        icon: "fileText",
        href: route("invoices.index"),
    },
];

function formatCurrency(amount) {
    return new Intl.NumberFormat("en-NG", {
        style: "currency",
        currency: "NGN",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount || 0);
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Dashboard
                </h2>
                <p class="text-sm text-gray-500">
                    {{
                        $page.props.scopeLabel
                            ? `${$page.props.scopeLabel} · ${today}`
                            : $page.props.activeBranch
                              ? `${$page.props.activeBranch.name} · ${today}`
                              : today
                    }}
                </p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Primary summary cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="stat in summaryCards"
                    :key="stat.label"
                    :href="stat.href"
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">
                            {{ stat.label }}
                        </p>
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-lg"
                            :class="stat.accent"
                        >
                            <AppIcon
                                :name="stat.icon"
                                classes="[&>svg]:h-5 [&>svg]:w-5"
                            />
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ stat.value }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                        {{ stat.detail }}
                        <span class="font-medium text-indigo-500">
                            · View details
                        </span>
                    </p>
                </Link>
            </div>

            <!-- Secondary metrics -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <Link
                    v-for="item in secondaryCards"
                    :key="item.label"
                    :href="item.href"
                    class="rounded-lg border border-gray-100 bg-white p-4 text-center shadow-sm transition hover:border-indigo-300 hover:shadow-md"
                >
                    <AppIcon
                        :name="item.icon"
                        classes="[&>svg]:h-5 [&>svg]:w-5 mx-auto text-gray-400"
                    />
                    <p class="mt-2 text-lg font-bold text-gray-900">
                        {{ item.value }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">{{ item.label }}</p>
                </Link>
            </div>

            <!-- Room Type Breakdown -->
            <div
                v-if="room_type_breakdown && room_type_breakdown.length"
                class="rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-100 px-5 py-4"
                >
                    <h3 class="text-sm font-semibold text-gray-900">
                        Room Type Breakdown
                    </h3>
                    <Link
                        :href="route('room-types.index')"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        View room types
                    </Link>
                </div>
                <div class="p-5">
                    <div class="space-y-3">
                        <div
                            v-for="type in room_type_breakdown"
                            :key="type.type"
                            class="flex items-center justify-between"
                        >
                            <span class="text-sm text-gray-700">{{
                                type.type
                            }}</span>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-gray-500">
                                    {{ type.occupied }}/{{ type.total }}
                                    occupied
                                </span>
                                <div
                                    class="h-2 w-24 overflow-hidden rounded-full bg-gray-100"
                                >
                                    <div
                                        class="h-full rounded-full bg-indigo-500"
                                        :style="{
                                            width:
                                                type.total > 0
                                                    ? `${(type.occupied / type.total) * 100}%`
                                                    : '0%',
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent reservations -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div
                    class="flex items-center justify-between border-b border-gray-100 px-5 py-4"
                >
                    <h3 class="text-sm font-semibold text-gray-900">
                        Recent Reservations
                    </h3>
                    <Link
                        :href="route('reservations.index')"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                    >
                        View All
                    </Link>
                </div>
                <div
                    v-if="recent_reservations && recent_reservations.length"
                    class="overflow-x-auto"
                >
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Code
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Guest
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Room
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Dates
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr
                                v-for="reservation in recent_reservations"
                                :key="reservation.id"
                            >
                                <td
                                    class="whitespace-nowrap px-5 py-3 text-sm font-medium text-indigo-600"
                                >
                                    {{ reservation.code }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-3 text-sm text-gray-600"
                                >
                                    {{ reservation.guest }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-3 text-sm text-gray-600"
                                >
                                    {{ reservation.room }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-3 text-sm text-gray-500"
                                >
                                    {{ reservation.check_in }} →
                                    {{ reservation.check_out }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="reservation.badge_class"
                                    >
                                        {{ reservation.status_label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-else
                    class="flex flex-col items-center justify-center px-5 py-14 text-center"
                >
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-300"
                    >
                        <AppIcon
                            name="calendar"
                            classes="[&>svg]:h-6 [&>svg]:w-6"
                        />
                    </span>
                    <p class="mt-4 text-sm font-medium text-gray-900">
                        No reservations yet
                    </p>
                    <p class="mt-1 text-sm text-gray-400">
                        Reservations will appear here once created.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
