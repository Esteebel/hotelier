<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    checkout: Object,
    activeBranch: Object,
});

const statusStyle =
    props.checkout.status === "completed"
        ? "bg-emerald-100 text-emerald-800"
        : "bg-rose-100 text-rose-700";
const statusLabel =
    props.checkout.status === "completed" ? "Completed" : "Cancelled";

function formatCurrency(amount) {
    return new Intl.NumberFormat("en-NG", {
        style: "currency",
        currency: "NGN",
    }).format(amount || 0);
}
</script>

<template>
    <Head :title="`Check-out ${checkout.checkout_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-900"
                    >
                        {{ checkout.checkout_number }}
                    </h2>
                    <p class="text-sm text-gray-500">Check-out Details</p>
                </div>
                <Link
                    :href="route('checkouts.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    Back to Check-outs
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <h3
                                class="mb-4 text-sm font-semibold text-gray-900"
                            >
                                Check-out Information
                            </h3>
                            <dl class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="text-gray-500">Check-out #</dt>
                                    <dd class="mt-1 font-medium text-gray-900">
                                        {{ checkout.checkout_number }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="statusStyle"
                                        >
                                            {{ statusLabel }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Room</dt>
                                    <dd class="mt-1 font-medium text-gray-900">
                                        {{ checkout.room?.room_number ?? "-" }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">
                                        Checked Out At
                                    </dt>
                                    <dd class="mt-1 font-medium text-gray-900">
                                        {{ checkout.checked_out_at }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Processed By</dt>
                                    <dd class="mt-1 font-medium text-gray-900">
                                        {{
                                            checkout.processor
                                                ? `${checkout.processor.first_name} ${checkout.processor.last_name}`
                                                : "-"
                                        }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <h3
                                class="mb-4 text-sm font-semibold text-gray-900"
                            >
                                Financial Summary
                            </h3>
                            <dl class="space-y-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <dt class="text-gray-500">Total Charges</dt>
                                    <dd class="font-medium text-gray-900">
                                        {{
                                            formatCurrency(
                                                checkout.total_charges,
                                            )
                                        }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-gray-500">Total Paid</dt>
                                    <dd class="font-medium text-gray-900">
                                        {{
                                            formatCurrency(checkout.total_paid)
                                        }}
                                    </dd>
                                </div>
                                <div
                                    class="flex items-center justify-between border-t border-gray-100 pt-3"
                                >
                                    <dt class="font-semibold text-gray-900">
                                        Balance Due
                                    </dt>
                                    <dd
                                        class="font-semibold"
                                        :class="
                                            checkout.balance_due > 0
                                                ? 'text-red-600'
                                                : 'text-emerald-600'
                                        "
                                    >
                                        {{
                                            formatCurrency(checkout.balance_due)
                                        }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div
                            v-if="checkout.notes"
                            class="rounded-lg bg-white p-6 shadow-sm"
                        >
                            <h3
                                class="mb-2 text-sm font-semibold text-gray-900"
                            >
                                Notes
                            </h3>
                            <p class="text-sm text-gray-600">
                                {{ checkout.notes }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <h3
                                class="mb-4 text-sm font-semibold text-gray-900"
                            >
                                Guest Details
                            </h3>
                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-gray-500">Name</dt>
                                    <dd class="mt-1 font-medium text-gray-900">
                                        {{
                                            checkout.guest
                                                ? `${checkout.guest.first_name} ${checkout.guest.last_name}`
                                                : "-"
                                        }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Email</dt>
                                    <dd class="mt-1 text-gray-900">
                                        {{ checkout.guest?.email ?? "-" }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Phone</dt>
                                    <dd class="mt-1 text-gray-900">
                                        {{ checkout.guest?.phone ?? "-" }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
