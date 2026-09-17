<script setup>
import { Head } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const today = new Date().toLocaleDateString("en-GB", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
});

const stats = [
    {
        label: "Occupancy Today",
        value: "0%",
        detail: "0 of 0 rooms occupied",
        icon: "trendingUp",
        accent: "bg-indigo-100 text-indigo-700",
    },
    {
        label: "Today's Arrivals",
        value: "0",
        detail: "expected check-ins",
        icon: "userRound",
        accent: "bg-emerald-100 text-emerald-700",
    },
    {
        label: "Today's Check-outs",
        value: "0",
        detail: "due to depart",
        icon: "door",
        accent: "bg-amber-100 text-amber-700",
    },
    {
        label: "Revenue Today",
        value: "₦0.00",
        detail: "confirmed payments",
        icon: "banknote",
        accent: "bg-rose-100 text-rose-700",
    },
];
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
                        $page.props.activeBranch
                            ? `${$page.props.activeBranch.name} · ${today}`
                            : today
                    }}
                </p>
            </div>
        </template>

        <!-- Summary stats -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
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
                <p class="mt-1 text-xs text-gray-400">{{ stat.detail }}</p>
            </div>
        </div>

        <!-- Recent reservations -->
        <div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm">
            <div
                class="flex items-center justify-between border-b border-gray-100 px-5 py-4"
            >
                <h3 class="text-sm font-semibold text-gray-900">
                    Recent Reservations
                </h3>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500"
                >
                    <AppIcon
                        name="calendar"
                        classes="[&>svg]:h-3.5 [&>svg]:w-3.5"
                    />
                    Awaiting data
                </span>
            </div>
            <div
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
                    Reservations will appear here once the Reservations module
                    is live.
                </p>
            </div>
        </div>

        <!-- Rooms overview -->
        <div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm">
            <div
                class="flex items-center justify-between border-b border-gray-100 px-5 py-4"
            >
                <h3 class="text-sm font-semibold text-gray-900">
                    Room Availability
                </h3>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500"
                >
                    <AppIcon name="bed" classes="[&>svg]:h-3.5 [&>svg]:w-3.5" />
                    0 rooms
                </span>
            </div>
            <div
                class="flex flex-col items-center justify-center px-5 py-14 text-center"
            >
                <span
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-300"
                >
                    <AppIcon name="bed" classes="[&>svg]:h-6 [&>svg]:w-6" />
                </span>
                <p class="mt-4 text-sm font-medium text-gray-900">
                    No rooms configured
                </p>
                <p class="mt-1 text-sm text-gray-400">
                    Room availability will appear here once branches and rooms
                    are set up.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
