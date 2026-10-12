<script setup>
import { ref, watch } from "vue";
import { Head, router, Link, useForm } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { formatMoney } from "@/Utils/format.js";

const props = defineProps({
    metrics: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    activeBranch: { type: Object, default: null },
});

const from = ref(props.filters.from ?? "");
const to = ref(props.filters.to ?? "");

const apply = () => {
    router.get(
        route("reports.index"),
        {
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const exportReport = (type) => {
    const params = new URLSearchParams();
    if (from.value) params.set("from", from.value);
    if (to.value) params.set("to", to.value);
    params.set("type", type);
    window.location.href = route("reports.export") + "?" + params.toString();
};

const metricCards = [
    {
        key: "occupancy",
        label: "Occupancy Rate",
        icon: "chart",
        format: (v) => `${v ?? 0}%`,
        href: route("rooms.index"),
    },
    {
        key: "revenue_paid",
        label: "Revenue (Paid)",
        icon: "card",
        format: (v) => formatMoney(v, { decimals: 0 }),
        href: route("payments.index", { status: "completed" }),
    },
    {
        key: "revenue_pending",
        label: "Revenue (Pending)",
        icon: "banknote",
        format: (v) => formatMoney(v, { decimals: 0 }),
        href: route("payments.index", { status: "pending" }),
    },
    {
        key: "reservations",
        label: "Reservations",
        icon: "calendar",
        format: (v) => v ?? 0,
        href: route("reservations.index"),
    },
    {
        key: "checked_in",
        label: "Checked In",
        icon: "users",
        format: (v) => v ?? 0,
        href: route("reservations.index", { status: "checked_in" }),
    },
    {
        key: "cancelled",
        label: "Cancelled",
        icon: "chart",
        format: (v) => v ?? 0,
        href: route("reservations.index", { status: "cancelled" }),
    },
    {
        key: "total_rooms",
        label: "Total Rooms",
        icon: "bed",
        format: (v) => v ?? 0,
        href: route("rooms.index"),
    },
    {
        key: "out_of_service",
        label: "Out of Service",
        icon: "layers",
        format: (v) => v ?? 0,
        href: route("rooms.index", { status: "out_of_service" }),
    },
    {
        key: "invoices_outstanding",
        label: "Invoices Outstanding",
        icon: "receipt",
        format: (v) => formatMoney(v, { decimals: 0 }),
        href: route("invoices.index"),
    },
];
</script>

<template>
    <Head title="Reports & Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-1 items-center justify-between gap-4">
                <div>
                    <h2>Reports & Analytics</h2>
                    <p>
                        {{
                            activeBranch
                                ? `${activeBranch.name} (${activeBranch.code})`
                                : "All branches"
                        }}
                    </p>
                </div>
            </div>
        </template>

        <div
            class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
        >
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:items-end">
                <div>
                    <InputLabel value="From" />
                    <TextInput
                        v-model="from"
                        type="date"
                        class="mt-1 block w-full"
                    />
                </div>
                <div>
                    <InputLabel value="To" />
                    <TextInput
                        v-model="to"
                        type="date"
                        class="mt-1 block w-full"
                    />
                </div>
                <div class="flex gap-2">
                    <PrimaryButton type="button" @click="apply"
                        >Apply</PrimaryButton
                    >
                    <SecondaryButton
                        type="button"
                        @click="
                            from = '';
                            to = '';
                            apply();
                        "
                        >Reset</SecondaryButton
                    >
                </div>
            </div>
            <div class="mt-4 flex gap-2 border-t border-gray-100 pt-4">
                <span class="text-sm font-medium text-gray-500">Export:</span>
                <button
                    @click="exportReport('reservations')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    Reservations CSV
                </button>
                <span class="text-gray-300">|</span>
                <button
                    @click="exportReport('payments')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    Payments CSV
                </button>
                <span class="text-gray-300">|</span>
                <button
                    @click="exportReport('guests')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    Guests CSV
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="card in metricCards"
                :key="card.key"
                :href="card.href"
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">
                        {{ card.label }}
                    </p>
                    <span class="rounded-lg bg-indigo-50 p-1.5 text-indigo-600">
                        <AppIcon
                            :name="card.icon"
                            classes="[&>svg]:h-4 [&>svg]:w-4"
                        />
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold text-gray-900">
                    {{ card.format(metrics[card.key]) }}
                </p>
                <p class="mt-1 text-xs font-medium text-indigo-500">
                    View details
                </p>
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
