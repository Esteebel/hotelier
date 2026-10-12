<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import AppIcon from "@/Components/AppIcon.vue";

const props = defineProps({
    checkin: Object,
    activeBranch: Object,
});

const statusStyle =
    props.checkin.status === "checked_in"
        ? "bg-emerald-100 text-emerald-800"
        : "bg-rose-100 text-rose-700";
const statusLabel =
    props.checkin.status === "checked_in" ? "Checked In" : "Cancelled";
</script>

<template>
    <Head :title="`Check-in ${checkin.checkin_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-900"
                    >
                        {{ checkin.checkin_number }}
                    </h2>
                    <p class="text-sm text-gray-500">Check-in Details</p>
                </div>
                <Link
                    :href="route('checkins.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    Back to Check-ins
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
                                Check-in Information
                            </h3>
                            <dl class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="text-gray-500">Check-in #</dt>
                                    <dd class="mt-1 font-medium text-gray-900">
                                        {{ checkin.checkin_number }}
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
                                    <dt class="text-gray-500">
                                        Reservation Code
                                    </dt>
                                    <dd class="mt-1 font-medium text-gray-900">
                                        {{
                                            checkin.reservation
                                                ?.reservation_number ?? "-"
                                        }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Room</dt>
                                    <dd class="mt-1 font-medium text-gray-900">
                                        {{ checkin.room?.room_number ?? "-" }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Checked In At</dt>
                                    <dd class="mt-1 font-medium text-gray-900">
                                        {{ checkin.checked_in_at }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Processed By</dt>
                                    <dd class="mt-1 font-medium text-gray-900">
                                        {{
                                            checkin.processor
                                                ? checkin.processor.name
                                                : "-"
                                        }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div
                            v-if="checkin.notes"
                            class="rounded-lg bg-white p-6 shadow-sm"
                        >
                            <h3
                                class="mb-2 text-sm font-semibold text-gray-900"
                            >
                                Notes
                            </h3>
                            <p class="text-sm text-gray-600">
                                {{ checkin.notes }}
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
                                            checkin.guest
                                                ? `${checkin.guest.first_name} ${checkin.guest.last_name}`
                                                : "-"
                                        }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Email</dt>
                                    <dd class="mt-1 text-gray-900">
                                        {{ checkin.guest?.email ?? "-" }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Phone</dt>
                                    <dd class="mt-1 text-gray-900">
                                        {{ checkin.guest?.phone ?? "-" }}
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
