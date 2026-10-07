<script setup>
import { Head } from "@inertiajs/vue3";
import CustomerLayout from "@/Layouts/CustomerLayout.vue";

const props = defineProps({
    profile: Object,
    guest: Object,
});

const initials = (name) =>
    (name ?? "")
        .split(" ")
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join("")
        .toUpperCase();
</script>

<template>
    <Head title="Profile" />

    <CustomerLayout>
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Profile</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Your account details as held by the hotel.
                </p>
            </div>

            <div class="space-y-6">
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <div class="flex items-center gap-4">
                        <span
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-lg font-semibold text-white"
                        >
                            {{ initials(profile.name) }}
                        </span>
                        <div>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ profile.name }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ profile.email }}
                            </p>
                            <span
                                class="mt-1 inline-block rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700"
                            >
                                {{ profile.user_type_label }}
                            </span>
                        </div>
                    </div>

                    <dl
                        class="mt-6 grid grid-cols-1 gap-4 border-t border-gray-100 pt-6 sm:grid-cols-2"
                    >
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Name
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ profile.name }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Email
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ profile.email }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div
                    v-if="guest"
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h2 class="text-base font-semibold text-gray-900">
                        Linked Guest Profile
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Used for your reservations, bills and orders.
                    </p>

                    <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Guest
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ guest.first_name }}
                                {{ guest.last_name }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Phone
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ guest.phone || "—" }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Email
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ guest.email || "—" }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Nationality
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ guest.nationality || "—" }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Address
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ guest.address || "—" }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-8 text-center"
                >
                    <p class="text-sm font-medium text-gray-900">
                        No guest profile linked
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        Contact reception to link your account to a guest
                        profile.
                    </p>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
