<script setup>
import { ref } from "vue";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
    tasks: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const statusFilter = ref(props.filters?.status ?? "");

const statusStyles = {
    pending: "bg-amber-100 text-amber-800",
    completed: "bg-emerald-100 text-emerald-800",
    cancelled: "bg-rose-100 text-rose-700",
};

const statusLabels = {
    pending: "Pending",
    completed: "Completed",
    cancelled: "Cancelled",
};

function applyFilters() {
    router.get(
        route("room-tasks.index"),
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
                Room Transfers
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
                                placeholder="Search tasks…"
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
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link
                            href="/room-tasks/create"
                            class="bg-indigo-600 text-white hover:bg-indigo-700 inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold"
                        >
                            <AppIcon name="plus" class="mr-2 h-4 w-4" />
                            New Transfer
                        </Link>
                    </div>
                </div>

                <div class="mt-6 bg-white rounded-lg shadow-sm">
                    <div
                        v-if="tasks.data && tasks.data.length"
                        class="overflow-x-auto"
                    >
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Transfer
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Reservation
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        From → To
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
                                    v-for="transfer in tasks.data"
                                    :key="transfer.id"
                                >
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900"
                                    >
                                        {{ transfer.task_number }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{
                                            transfer.reservation
                                                ?.reservation_number
                                        }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        <span class="text-gray-900">{{
                                            transfer.from_room
                                        }}</span>
                                        <AppIcon
                                            name="arrow-right"
                                            class="mx-1 inline h-3 w-3"
                                        />
                                        <span class="text-gray-900">{{
                                            transfer.to_room
                                        }}</span>
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{ transfer.due_date }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="
                                                statusStyles[transfer.status] ??
                                                statusStyles.pending
                                            "
                                        >
                                            {{
                                                statusLabels[transfer.status] ??
                                                transfer.status
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium"
                                    >
                                        <Link
                                            :href="`/room-tasks/${transfer.id}/edit`"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            >Edit</Link
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-else
                        class="px-6 py-12 text-center text-sm text-gray-500"
                    >
                        No room tasks found.
                    </div>
                </div>

                <nav class="flex items-center gap-1">
                    <template v-for="link in tasks.links" :key="link.label">
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
