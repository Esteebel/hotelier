<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SelectInput from "@/Components/SelectInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    settings: {
        type: Object,
        default: null,
    },
});

const currencies = [
    { value: "NGN", label: "NGN — Nigerian Naira" },
    { value: "USD", label: "USD — US Dollar" },
    { value: "GBP", label: "GBP — British Pound" },
    { value: "EUR", label: "EUR — Euro" },
];

const timezones = [
    "Africa/Abidjan",
    "Africa/Accra",
    "Africa/Algiers",
    "Africa/Cairo",
    "Africa/Casablanca",
    "Africa/Johannesburg",
    "Africa/Lagos",
    "Africa/Nairobi",
    "Africa/Tunis",
    "America/Chicago",
    "America/Denver",
    "America/Los_Angeles",
    "America/Mexico_City",
    "America/New_York",
    "America/Sao_Paulo",
    "America/Toronto",
    "Asia/Bangkok",
    "Asia/Beirut",
    "Asia/Dubai",
    "Asia/Hong_Kong",
    "Asia/Jakarta",
    "Asia/Jerusalem",
    "Asia/Karachi",
    "Asia/Kolkata",
    "Asia/Kuala_Lumpur",
    "Asia/Manila",
    "Asia/Riyadh",
    "Asia/Seoul",
    "Asia/Shanghai",
    "Asia/Singapore",
    "Asia/Tokyo",
    "Australia/Melbourne",
    "Australia/Perth",
    "Australia/Sydney",
    "Europe/Amsterdam",
    "Europe/Athens",
    "Europe/Berlin",
    "Europe/Brussels",
    "Europe/Dublin",
    "Europe/Helsinki",
    "Europe/Lisbon",
    "Europe/London",
    "Europe/Madrid",
    "Europe/Paris",
    "Europe/Prague",
    "Europe/Rome",
    "Europe/Warsaw",
    "Europe/Zurich",
    "Pacific/Auckland",
    "Pacific/Honolulu",
    "UTC",
];

const form = useForm({
    brand_name: props.settings?.brand_name ?? "",
    default_currency: props.settings?.default_currency ?? "NGN",
    tax_rate: props.settings?.tax_rate ?? 7.5,
    check_in_time: props.settings?.check_in_time ?? "14:00",
    check_out_time: props.settings?.check_out_time ?? "12:00",
    timezone: props.settings?.timezone ?? "Africa/Lagos",
    default_page_size: props.settings?.default_page_size ?? 10,
});

const submit = () => {
    form.put(route("settings.update"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Settings
                </h2>
                <p class="text-sm text-gray-500">
                    Configure global application preferences.
                </p>
            </div>
        </template>

        <!-- Flash message -->
        <div
            v-if="$page.props.flash?.success"
            class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
            {{ $page.props.flash.success }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Branding -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h3 class="text-sm font-semibold text-gray-900">Branding</h3>
                <p class="mt-0.5 text-sm text-gray-500">
                    The name used to identify your hotel across the system.
                </p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel value="Brand Name" />
                        <TextInput
                            v-model="form.brand_name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g. Heritage Hotels"
                            maxlength="100"
                            required
                            autofocus
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.brand_name"
                        />
                    </div>
                </div>
            </div>

            <!-- Preferences -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h3 class="text-sm font-semibold text-gray-900">Preferences</h3>
                <p class="mt-0.5 text-sm text-gray-500">
                    Defaults applied throughout the application.
                </p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Default Currency" />
                        <SelectInput
                            v-model="form.default_currency"
                            class="mt-1 block w-full"
                            required
                        >
                            <option
                                v-for="currency in currencies"
                                :key="currency.value"
                                :value="currency.value"
                            >
                                {{ currency.label }}
                            </option>
                        </SelectInput>
                        <InputError
                            class="mt-2"
                            :message="form.errors.default_currency"
                        />
                    </div>

                    <div>
                        <InputLabel value="Tax Rate (%)" />
                        <TextInput
                            v-model.number="form.tax_rate"
                            type="number"
                            min="0"
                            max="100"
                            step="0.5"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.tax_rate"
                        />
                    </div>

                    <div>
                        <InputLabel value="Timezone" />
                        <SelectInput
                            v-model="form.timezone"
                            class="mt-1 block w-full"
                            required
                        >
                            <option
                                v-for="zone in timezones"
                                :key="zone"
                                :value="zone"
                            >
                                {{ zone }}
                            </option>
                        </SelectInput>
                        <InputError
                            class="mt-2"
                            :message="form.errors.timezone"
                        />
                    </div>

                    <div>
                        <InputLabel value="Default Page Size" />
                        <TextInput
                            v-model.number="form.default_page_size"
                            type="number"
                            class="mt-1 block w-full"
                            min="5"
                            max="100"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.default_page_size"
                        />
                    </div>
                </div>
            </div>

            <!-- Hotel hours -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h3 class="text-sm font-semibold text-gray-900">Hotel Hours</h3>
                <p class="mt-0.5 text-sm text-gray-500">
                    Standard check-in and check-out times for reservations.
                </p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Check-in Time" />
                        <TextInput
                            v-model="form.check_in_time"
                            type="time"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.check_in_time"
                        />
                    </div>

                    <div>
                        <InputLabel value="Check-out Time" />
                        <TextInput
                            v-model="form.check_out_time"
                            type="time"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.check_out_time"
                        />
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25"
                >
                    <AppIcon
                        v-if="!form.processing"
                        name="check"
                        classes="[&>svg]:h-4 [&>svg]:w-4"
                    />
                    {{ form.processing ? "Saving..." : "Save Settings" }}
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
