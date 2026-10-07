<script setup>
import { ref } from "vue";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { usePermissions } from "@/Composables/usePermissions";

const props = defineProps({
    tasks: Object,
    filters: Object,
    activeBranch: Object,
});

const search = ref(props.filters?.search ?? "");
const statusFilter = ref(props.filters?.status ?? "");
const { hasPermission } = usePermissions();

const statusStyles = {
    pending: "bg-amber-100 text-amber-800",
    "in-progress": "bg-blue-100 text-blue-800",
    completed: "bg-emerald-100 text-emerald-800",
    cancelled: "bg-rose-100 text-rose-700",
};
const priorityStyles = {
    low: "bg-gray-100 text-gray-600",
    normal: "bg-blue-100 text-blue-700",
    high: "bg-orange-100 text-orange-700",
    urgent: "bg-red-100 text-red-700",
};

function applyFilters() {
    router.get(
        route("maintenance.index"),
        { search: search.value, status: statusFilter.value },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header
            ><h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Maintenance
            </h2></template
        >
        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search..."
                            @keydown.enter="applyFilters"
                            class="w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="in-progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <Link
                        v-if="hasPermission('manage-maintenance')"
                        :href="route('maintenance.create')"
                        class="bg-indigo-600 text-white hover:bg-indigo-700 inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold"
                    >
                        <AppIcon name="plus" class="mr-2 h-4 w-4" />New Task
                    </Link>
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
                                        Task #
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Room
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Type
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Assigned To
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Priority
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Due
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
                                <tr v-for="task in tasks.data" :key="task.id">
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm font-medium text-indigo-600"
                                    >
                                        {{ task.task_number }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{ task.room?.room_number ?? "-" }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{ task.task_type }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{ task.assigned_to ?? "-" }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="
                                                priorityStyles[task.priority]
                                            "
                                            >{{ task.priority }}</span
                                        >
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{ task.due_date }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="statusStyles[task.status]"
                                            >{{ task.status }}</span
                                        >
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium"
                                    >
                                        <Link
                                            v-if="
                                                hasPermission(
                                                    'manage-maintenance',
                                                )
                                            "
                                            :href="
                                                route(
                                                    'maintenance.edit',
                                                    task.id,
                                                )
                                            "
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
                        No tasks found.
                    </div>
                </div>
                <nav class="mt-4 flex items-center gap-1">
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
