<script setup>
import { ref } from "vue";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
    checkins: Object,
    filters: Object,
    activeBranch: Object,
});

const search = ref(props.filters?.search ?? "");
const statusFilter = ref(props.filters?.status ?? "");

const statusStyles = {
    checked_in: "bg-emerald-100 text-emerald-800",
    cancelled: "bg-rose-100 text-rose-700",
};

const statusLabels = {
    checked_in: "Checked In",
    cancelled: "Cancelled",
};

function applyFilters() {
    router.get(
        route("checkins.index"),
        {
            search: search.value,
            status: statusFilter.value,
        },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Check-ins
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search check-ins..."
                                @keydown.enter="applyFilters"
                                class="w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All statuses</option>
                            <option value="checked_in">Checked In</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <Link
                        :href="route('checkins.create')"
                        class="bg-indigo-600 text-white hover:bg-indigo-700 inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold"
                    >
                        <AppIcon name="plus" class="mr-2 h-4 w-4" />
                        New Check-in
                    </Link>
                </div>

                <div class="mt-6 bg-white rounded-lg shadow-sm">
                    <div
                        v-if="checkins.data && checkins.data.length"
                        class="overflow-x-auto"
                    >
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Check-in #
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Guest
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Room
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Reservation
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Date
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="checkin in checkins.data"
                                    :key="checkin.id"
                                >
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm font-medium text-indigo-600"
                                    >
                                        {{ checkin.checkin_number }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{
                                            checkin.guest
                                                ? `${checkin.guest.first_name} ${checkin.guest.last_name}`
                                                : "-"
                                        }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{ checkin.room?.room_number ?? "-" }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{
                                            checkin.reservation
                                                ?.reservation_number ?? "-"
                                        }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{ checkin.checked_in_at }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="
                                                statusStyles[checkin.status] ??
                                                statusStyles.checked_in
                                            "
                                        >
                                            {{
                                                statusLabels[checkin.status] ??
                                                checkin.status
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'checkins.show',
                                                    checkin.id,
                                                )
                                            "
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div
                        v-else
                        class="px-6 py-12 text-center text-sm text-gray-500"
                    >
                        No check-ins found.
                    </div>
                </div>

                <nav class="mt-4 flex items-center gap-1">
                    <template v-for="link in checkins.links" :key="link.label">
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
        </div>
    </AuthenticatedLayout>
</template>
