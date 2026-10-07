<script setup>
import { ref } from "vue";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { usePermissions } from "@/Composables/usePermissions";

const props = defineProps({
    staff: Object,
    filters: Object,
    activeBranch: Object,
});

const search = ref(props.filters?.search ?? "");
const statusFilter = ref(props.filters?.status ?? "");
const { hasPermission } = usePermissions();

const statusStyles = {
    active: "bg-emerald-100 text-emerald-800",
    inactive: "bg-gray-100 text-gray-600",
    on_leave: "bg-amber-100 text-amber-800",
};

function applyFilters() {
    router.get(
        route("staff.index"),
        { search: search.value, status: statusFilter.value },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Staff
            </h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search staff..."
                            @keydown.enter="applyFilters"
                            class="w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="on_leave">On Leave</option>
                        </select>
                    </div>
                    <Link
                        v-if="hasPermission('manage-staff')"
                        :href="route('staff.create')"
                        class="bg-indigo-600 text-white hover:bg-indigo-700 inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold"
                    >
                        <AppIcon name="plus" class="mr-2 h-4 w-4" />
                        Add Staff
                    </Link>
                </div>
                <div class="mt-6 bg-white rounded-lg shadow-sm">
                    <div
                        v-if="staff.data && staff.data.length"
                        class="overflow-x-auto"
                    >
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Staff #
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Name
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Position
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Department
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
                                    v-for="member in staff.data"
                                    :key="member.id"
                                >
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm font-medium text-indigo-600"
                                    >
                                        {{ member.staff_number }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-900"
                                    >
                                        {{ member.first_name }}
                                        {{ member.last_name }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{ member.position }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{ member.department ?? "-" }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="
                                                statusStyles[member.status] ??
                                                statusStyles.active
                                            "
                                        >
                                            {{
                                                member.status?.replace("_", " ")
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium"
                                    >
                                        <Link
                                            v-if="hasPermission('manage-staff')"
                                            :href="
                                                route('staff.edit', member.id)
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
                        No staff members found.
                    </div>
                </div>
                <nav class="mt-4 flex items-center gap-1">
                    <template v-for="link in staff.links" :key="link.label">
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
