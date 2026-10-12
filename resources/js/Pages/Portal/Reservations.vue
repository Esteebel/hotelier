<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import CustomerLayout from "@/Layouts/CustomerLayout.vue";
import { formatMoney } from "@/Utils/format.js";

const props = defineProps({
    guest: Object,
    reservations: Object,
});

const statusStyles = {
    confirmed: "bg-blue-100 text-blue-800",
    checked_in: "bg-emerald-100 text-emerald-700",
    checked_out: "bg-gray-100 text-gray-600",
    cancelled: "bg-rose-100 text-rose-700",
    pending: "bg-amber-100 text-amber-800",
};

const cancelReservation = (reservation) => {
    if (
        !window.confirm(
            `Cancel reservation ${reservation.reservation_number}? This cannot be undone.`,
        )
    ) {
        return;
    }

    router.post(
        route("portal.reservations.cancel", reservation.id),
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="My Reservations" />

    <CustomerLayout>
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        My Reservations
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Your past and upcoming stays.
                    </p>
                </div>
                <Link
                    :href="route('portal.rooms')"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                >
                    <AppIcon name="bed" classes="[&>svg]:h-4 [&>svg]:w-4" />
                    Book a room
                </Link>
            </div>

            <div
                v-if="$page.props.flash?.success"
                class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            >
                {{ $page.props.flash.success }}
            </div>

            <div
                v-if="$page.props.errors?.reservation"
                class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"
            >
                {{ $page.props.errors.reservation }}
            </div>

            <div
                v-if="!guest"
                class="rounded-xl border border-gray-200 bg-white px-6 py-12 text-center shadow-sm"
            >
                <p class="text-sm font-medium text-gray-900">
                    No guest profile linked
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    Contact reception to link your reservations to this account.
                </p>
            </div>

            <div
                v-else
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <table
                    v-if="reservations.data.length"
                    class="min-w-full divide-y divide-gray-200"
                >
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Reservation
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Room
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Dates
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
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr
                            v-for="reservation in reservations.data"
                            :key="reservation.id"
                        >
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm font-medium text-indigo-600"
                            >
                                {{ reservation.reservation_number }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ reservation.room?.room_number ?? "—" }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ reservation.check_in }} →
                                {{ reservation.check_out }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900"
                            >
                                {{ formatMoney(reservation.total_amount) }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="
                                        statusStyles[reservation.status] ??
                                        'bg-gray-100 text-gray-600'
                                    "
                                >
                                    {{ reservation.status_label }}
                                </span>
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-right text-sm"
                            >
                                <button
                                    v-if="reservation.status === 'confirmed'"
                                    type="button"
                                    class="rounded-md px-2.5 py-1 text-sm font-medium text-rose-600 hover:bg-rose-50"
                                    @click="cancelReservation(reservation)"
                                >
                                    Cancel
                                </button>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div
                    v-if="reservations.total > reservations.per_page"
                    class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3"
                >
                    <p class="text-xs text-gray-500">
                        Showing
                        <span class="font-medium">{{ reservations.from }}</span>
                        to
                        <span class="font-medium">{{ reservations.to }}</span>
                        of
                        <span class="font-medium">{{
                            reservations.total
                        }}</span>
                        reservations
                    </p>
                    <nav class="flex items-center gap-1">
                        <template
                            v-for="link in reservations.links"
                            :key="link.label"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="rounded-md px-2.5 py-1 text-sm"
                                :class="
                                    link.active
                                        ? 'bg-indigo-600 font-medium text-white'
                                        : 'text-gray-600 hover:bg-gray-100'
                                "
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-2.5 py-1 text-sm text-gray-400"
                                v-html="link.label"
                            ></span>
                        </template>
                    </nav>
                </div>

                <div
                    v-if="!reservations.data.length"
                    class="px-6 py-12 text-center"
                >
                    <p class="text-sm font-medium text-gray-900">
                        No reservations yet
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        Your bookings will appear here.
                        <Link
                            :href="route('portal.rooms')"
                            class="font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Browse available rooms
                        </Link>
                        to make your first reservation.
                    </p>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
