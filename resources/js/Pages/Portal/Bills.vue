<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import CustomerLayout from "@/Layouts/CustomerLayout.vue";
import { formatMoney } from "@/Utils/format.js";

const props = defineProps({
    guest: Object,
    invoices: Object,
    payments: Object,
});

const tab = ref("invoices");

const invoiceStatusStyles = {
    paid: "bg-emerald-100 text-emerald-700",
    partially_paid: "bg-amber-100 text-amber-800",
    unpaid: "bg-rose-100 text-rose-700",
    void: "bg-gray-100 text-gray-600",
};
</script>

<template>
    <Head title="Bills & Payments" />

    <CustomerLayout>
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">
                    Bills & Payments
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Invoices issued to you and payments you have made.
                </p>
            </div>

            <div
                v-if="!guest"
                class="rounded-xl border border-gray-200 bg-white px-6 py-12 text-center shadow-sm"
            >
                <p class="text-sm font-medium text-gray-900">
                    No guest profile linked
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    Contact reception to link your bills to this account.
                </p>
            </div>

            <template v-else>
                <div class="mb-4 flex gap-2">
                    <button
                        type="button"
                        class="rounded-md px-4 py-2 text-sm font-medium transition"
                        :class="
                            tab === 'invoices'
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
                        "
                        @click="tab = 'invoices'"
                    >
                        Invoices
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-4 py-2 text-sm font-medium transition"
                        :class="
                            tab === 'payments'
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
                        "
                        @click="tab = 'payments'"
                    >
                        Payments
                    </button>
                </div>

                <div
                    v-show="tab === 'invoices'"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <table
                        v-if="invoices.data.length"
                        class="min-w-full divide-y divide-gray-200"
                    >
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Invoice
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Issued
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Total
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Paid
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr
                                v-for="invoice in invoices.data"
                                :key="invoice.id"
                            >
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm font-medium text-indigo-600"
                                >
                                    {{ invoice.invoice_number }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                                >
                                    {{ invoice.issued_on }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900"
                                >
                                    {{ formatMoney(invoice.total) }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                                >
                                    {{ formatMoney(invoice.amount_paid) }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        :class="
                                            invoiceStatusStyles[
                                                invoice.status
                                            ] ?? 'bg-gray-100 text-gray-600'
                                        "
                                    >
                                        {{
                                            (invoice.status ?? "").replace(
                                                "_",
                                                " ",
                                            )
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div
                        v-if="invoices.total > invoices.per_page"
                        class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3"
                    >
                        <p class="text-xs text-gray-500">
                            Showing
                            <span class="font-medium">{{ invoices.from }}</span>
                            to
                            <span class="font-medium">{{ invoices.to }}</span>
                            of
                            <span class="font-medium">{{
                                invoices.total
                            }}</span>
                            invoices
                        </p>
                        <nav class="flex items-center gap-1">
                            <template
                                v-for="link in invoices.links"
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
                        v-if="!invoices.data.length"
                        class="px-6 py-12 text-center"
                    >
                        <p class="text-sm font-medium text-gray-900">
                            No invoices yet
                        </p>
                    </div>
                </div>

                <div
                    v-show="tab === 'payments'"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <table
                        v-if="payments.data.length"
                        class="min-w-full divide-y divide-gray-200"
                    >
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Payment
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Invoice
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Method
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr
                                v-for="payment in payments.data"
                                :key="payment.id"
                            >
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm font-medium text-indigo-600"
                                >
                                    {{ payment.payment_number }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                                >
                                    {{ payment.invoice?.invoice_number ?? "—" }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                                >
                                    {{ payment.paid_on }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                                >
                                    {{ payment.method }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900"
                                >
                                    {{ formatMoney(payment.amount) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div
                        v-if="payments.total > payments.per_page"
                        class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3"
                    >
                        <p class="text-xs text-gray-500">
                            Showing
                            <span class="font-medium">{{ payments.from }}</span>
                            to
                            <span class="font-medium">{{ payments.to }}</span>
                            of
                            <span class="font-medium">{{
                                payments.total
                            }}</span>
                            payments
                        </p>
                        <nav class="flex items-center gap-1">
                            <template
                                v-for="link in payments.links"
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
                        v-if="!payments.data.length"
                        class="px-6 py-12 text-center"
                    >
                        <p class="text-sm font-medium text-gray-900">
                            No payments yet
                        </p>
                    </div>
                </div>
            </template>
        </div>
    </CustomerLayout>
</template>
