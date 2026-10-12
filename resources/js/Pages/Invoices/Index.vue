<script setup>
import { ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { usePermissions } from "@/Composables/usePermissions";
import { formatMoney as toMoney } from "@/Utils/format.js";

const props = defineProps({
    invoices: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    activeBranch: { type: Object, default: null },
});

const search = ref(props.filters.search ?? "");
const statusFilter = ref(props.filters.status ?? "");
const invoiceToDelete = ref(null);
const deleting = useForm({});
const { hasPermission } = usePermissions();

const reload = () => {
    router.get(
        route("invoices.index"),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["invoices"],
        },
    );
};

let searchTimer;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => reload(), 300);
});
watch(statusFilter, () => reload());

const confirmDelete = (invoice) => {
    invoiceToDelete.value = invoice;
};

const destroyInvoice = () => {
    deleting.delete(route("invoices.destroy", invoiceToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            invoiceToDelete.value = null;
        },
    });
};

const statusClass = (s) =>
    ({
        draft: "bg-gray-50 text-gray-700 ring-gray-600/20",
        sent: "bg-indigo-50 text-indigo-700 ring-indigo-600/20",
        partially_paid: "bg-amber-50 text-amber-700 ring-amber-600/20",
        paid: "bg-emerald-50 text-emerald-700 ring-emerald-600/20",
        overdue: "bg-red-50 text-red-700 ring-red-600/20",
        void: "bg-gray-100 text-gray-500 ring-gray-500/20",
    })[s] ?? "bg-gray-50 text-gray-700 ring-gray-600/20";

const statusLabel = (s) =>
    ({
        draft: "Draft",
        sent: "Sent",
        partially_paid: "Partially Paid",
        paid: "Paid",
        overdue: "Overdue",
        void: "Void",
    })[s] ?? s;

const formatMoney = (n) => toMoney(n, { decimals: 2 });
</script>

<template>
    <Head title="Invoices" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-1 items-center justify-between gap-4">
                <div>
                    <h2>Invoices</h2>
                    <p>
                        {{
                            activeBranch
                                ? `${activeBranch.name} (${activeBranch.code})`
                                : "All branches"
                        }}
                    </p>
                </div>
                <Link
                    v-if="hasPermission('manage-invoices')"
                    :href="route('invoices.create')"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                >
                    <AppIcon name="plus" classes="[&>svg]:h-4 [&>svg]:w-4" />
                    New Invoice
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
                    placeholder="Search invoices..."
                    class="w-full pl-9"
                />
            </div>
            <select
                v-model="statusFilter"
                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="partially_paid">Partially Paid</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
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
                                Number
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Guest
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Issued
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Due
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Total
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Balance
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
                        <tr v-for="invoice in invoices.data" :key="invoice.id">
                            <td class="whitespace-nowrap px-5 py-4">
                                <p
                                    class="font-mono text-sm font-medium text-indigo-600"
                                >
                                    {{ invoice.number }}
                                </p>
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-900"
                            >
                                {{ invoice.guest ?? "—" }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ invoice.issued_on }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ invoice.due_on ?? "—" }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900"
                            >
                                {{ formatMoney(invoice.total) }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ formatMoney(invoice.balance) }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                    :class="statusClass(invoice.status)"
                                >
                                    {{ statusLabel(invoice.status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div
                                    v-if="hasPermission('manage-invoices')"
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            route('invoices.edit', invoice.id)
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
                                        type="button"
                                        class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600"
                                        title="Delete"
                                        @click="confirmDelete(invoice)"
                                    >
                                        <AppIcon
                                            name="x"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!invoices.data.length">
                            <td colspan="8" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">
                                    No invoices found
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="invoices.total > invoices.per_page"
                class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3"
            >
                <p class="text-xs text-gray-500">
                    Showing {{ invoices.from }} to {{ invoices.to }} of
                    {{ invoices.total }} invoices
                </p>
                <nav class="flex items-center gap-1">
                    <template v-for="link in invoices.links" :key="link.label">
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

        <Modal :show="!!invoiceToDelete" @close="invoiceToDelete = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete invoice {{ invoiceToDelete?.number }}?
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    This will permanently remove this invoice record.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="invoiceToDelete = null"
                        >Cancel</SecondaryButton
                    >
                    <DangerButton
                        :disabled="deleting.processing"
                        @click="destroyInvoice"
                    >
                        {{
                            deleting.processing
                                ? "Deleting..."
                                : "Delete Invoice"
                        }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
