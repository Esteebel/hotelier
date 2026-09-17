<script setup>
import { onMounted, ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    reservations: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    activeBranch: {
        type: Object,
        default: null,
    },
});

const search = ref(props.filters.search ?? "");
const statusFilter = ref(props.filters.status ?? "");
const reservationToDelete = ref(null);
const deleting = useForm({});
const cancelling = useForm({});

const reload = () => {
    router.get(
        route("reservations.index"),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["reservations"],
        },
    );
};

let searchTimer;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(reload, 300);
});
watch(statusFilter, reload);

onMounted(() => {
    search.value = props.filters.search ?? "";
    statusFilter.value = props.filters.status ?? "";
});

const confirmDelete = (reservation) => {
    reservationToDelete.value = reservation;
};

const destroyReservation = () => {
    deleting.delete(
        route("reservations.destroy", reservationToDelete.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                reservationToDelete.value = null;
            },
        },
    );
};

const cancelReservation = (reservation) => {
    if (reservation.status === "cancelled") return;
    if (!confirm(`Cancel reservation ${reservation.code}?`)) return;
    cancelling.put(route("reservations.update", reservation.id), {
        _method: "put",
        status: "cancelled",
        preserveScroll: true,
    });
};

const statusBadge = (res) => {
    const map = {
        confirmed: "bg-emerald-50 text-emerald-700 ring-emerald-600/20",
        checked_in: "bg-indigo-50 text-indigo-700 ring-indigo-600/20",
        checked_out: "bg-gray-50 text-gray-700 ring-gray-600/20",
        cancelled: "bg-red-50 text-red-700 ring-red-600/20",
        no_show: "bg-amber-50 text-amber-700 ring-amber-600/20",
    };
    return map[res.status] ?? "bg-gray-50 text-gray-700 ring-gray-600/20";
};
</script>

<template>
    <Head title="Reservations" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-1 items-center justify-between gap-4">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-900"
                    >
                        Reservations
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{
                            activeBranch
                                ? `${activeBranch.name} (${activeBranch.code})`
                                : "Operating across your branches"
                        }}
                    </p>
                </div>
                <Link
                    :href="route('reservations.create')"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                >
                    <AppIcon name="plus" classes="[&>svg]:h-4 [&>svg]:w-4" />
                    New Reservation
                </Link>
            </div>
        </template>

        <div
            v-if="$page.props.flash?.success"
            class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
            {{ $page.props.flash.success }}
        </div>

        <div class="mb-4 flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1 sm:max-w-xs">
                <span
                    class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"
                >
                    <AppIcon name="search" classes="[&>svg]:h-4 [&>svg]:w-4" />
                </span>
                <TextInput
                    v-model="search"
                    type="search"
                    placeholder="Search reservations..."
                    class="w-full pl-9"
                />
            </div>
            <select
                v-model="statusFilter"
                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All statuses</option>
                <option value="confirmed">Confirmed</option>
                <option value="checked_in">Checked In</option>
                <option value="checked_out">Checked Out</option>
                <option value="cancelled">Cancelled</option>
                <option value="no_show">No Show</option>
            </select>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Code
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Guest
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Room
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Check-In
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Check-Out
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Nights
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Total
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Status
                            </th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="reservation in reservations.data"
                            :key="reservation.id"
                        >
                            <td class="whitespace-nowrap px-5 py-4">
                                <p
                                    class="font-mono text-sm font-medium text-indigo-600"
                                >
                                    {{ reservation.code }}
                                </p>
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-900"
                            >
                                {{ reservation.guest }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ reservation.room ?? "—" }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ reservation.check_in }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ reservation.check_out }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ reservation.nights }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900"
                            >
                                {{
                                    Number(
                                        reservation.total_amount ?? 0,
                                    ).toLocaleString()
                                }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                    :class="statusBadge(reservation)"
                                >
                                    {{ reservation.status_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        v-if="
                                            reservation.status !== 'cancelled'
                                        "
                                        :href="
                                            route(
                                                'reservations.edit',
                                                reservation.id,
                                            )
                                        "
                                        class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                                        title="Edit"
                                    >
                                        <AppIcon
                                            name="settings"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </Link>
                                    <button
                                        v-if="
                                            reservation.status !== 'cancelled'
                                        "
                                        type="button"
                                        class="rounded-md p-1.5 text-gray-400 hover:bg-amber-50 hover:text-amber-600"
                                        title="Cancel"
                                        @click="cancelReservation(reservation)"
                                    >
                                        <AppIcon
                                            name="x"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600"
                                        title="Delete"
                                        @click="confirmDelete(reservation)"
                                    >
                                        <AppIcon
                                            name="trash"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!reservations.data.length">
                            <td colspan="9" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">
                                    No reservations found
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
