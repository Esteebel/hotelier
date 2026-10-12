<script setup>
import { ref } from "vue";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { usePermissions } from "@/Composables/usePermissions";
import { formatMoney } from "@/Utils/format.js";

const props = defineProps({
    expenses: Object,
    filters: Object,
    activeBranch: Object,
});

const search = ref(props.filters?.search ?? "");
const statusFilter = ref(props.filters?.status ?? "");
const { hasPermission } = usePermissions();

function formatCurrency(amount) {
    return formatMoney(amount, { decimals: 2 });
}

function applyFilters() {
    router.get(
        route("expenses.index"),
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
                Expenses
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search expenses..."
                            @keydown.enter="applyFilters"
                            class="w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All statuses</option>
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <Link
                        v-if="hasPermission('manage-expenses')"
                        :href="route('expenses.create')"
                        class="bg-indigo-600 text-white hover:bg-indigo-700 inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold"
                    >
                        <AppIcon name="plus" class="mr-2 h-4 w-4" />
                        Record Expense
                    </Link>
                </div>

                <div class="mt-6 bg-white rounded-lg shadow-sm">
                    <div
                        v-if="expenses.data && expenses.data.length"
                        class="overflow-x-auto"
                    >
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Expense #
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Description
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Category
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Date
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Amount
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
                                    v-for="expense in expenses.data"
                                    :key="expense.id"
                                >
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm font-medium text-indigo-600"
                                    >
                                        {{ expense.expense_number }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ expense.description }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                                            >{{ expense.category }}</span
                                        >
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"
                                    >
                                        {{ expense.expense_date }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900"
                                    >
                                        {{ formatCurrency(expense.amount) }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium"
                                    >
                                        <Link
                                            v-if="
                                                hasPermission('manage-expenses')
                                            "
                                            :href="
                                                route(
                                                    'expenses.edit',
                                                    expense.id,
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
                        No expenses found.
                    </div>
                </div>

                <nav class="mt-4 flex items-center gap-1">
                    <template v-for="link in expenses.links" :key="link.label">
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
