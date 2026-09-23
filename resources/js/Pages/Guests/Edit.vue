<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    guest: { type: Object, required: true },
    activeBranch: { type: Object, default: null },
});

const form = useForm({
    first_name: props.guest.first_name ?? "",
    last_name: props.guest.last_name ?? "",
    email: props.guest.email ?? "",
    phone: props.guest.phone ?? "",
    birth_date: props.guest.birth_date ?? "",
    nationality: props.guest.nationality ?? "",
    id_number: props.guest.id_number ?? "",
    id_type: props.guest.id_type ?? "",
    address: props.guest.address ?? "",
    company: props.guest.company ?? "",
    notes: props.guest.notes ?? "",
    is_vip: props.guest.is_vip ?? false,
    status: props.guest.status ?? "active",
});

const submit = () => {
    form.put(route("guests.update", props.guest.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit ${guest.first_name ?? 'Guest'}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit Guest
                </h2>
                <p class="text-sm text-gray-500">
                    {{
                        activeBranch
                            ? `${activeBranch.name} (${activeBranch.code})`
                            : "All branches"
                    }}
                </p>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel value="First Name" for="first_name" />
                        <TextInput
                            id="first_name"
                            v-model="form.first_name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.first_name"
                        />
                    </div>

                    <div>
                        <InputLabel value="Last Name" for="last_name" />
                        <TextInput
                            id="last_name"
                            v-model="form.last_name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.last_name"
                        />
                    </div>

                    <div>
                        <InputLabel value="Email" for="email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel value="Phone" for="phone" />
                        <TextInput
                            id="phone"
                            v-model="form.phone"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.phone" />
                    </div>

                    <div>
                        <InputLabel value="Birth Date" for="birth_date" />
                        <TextInput
                            id="birth_date"
                            v-model="form.birth_date"
                            type="date"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.birth_date"
                        />
                    </div>

                    <div>
                        <InputLabel value="Nationality" for="nationality" />
                        <TextInput
                            id="nationality"
                            v-model="form.nationality"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.nationality"
                        />
                    </div>

                    <div>
                        <InputLabel value="ID Type" for="id_type" />
                        <SelectInput
                            id="id_type"
                            v-model="form.id_type"
                            class="mt-1 block w-full"
                        >
                            <option value="">Select ID type…</option>
                            <option value="passport">Passport</option>
                            <option value="national_id">National ID</option>
                            <option value="drivers_license">
                                Driver's License
                            </option>
                        </SelectInput>
                        <InputError
                            class="mt-2"
                            :message="form.errors.id_type"
                        />
                    </div>

                    <div>
                        <InputLabel value="ID Number" for="id_number" />
                        <TextInput
                            id="id_number"
                            v-model="form.id_number"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.id_number"
                        />
                    </div>

                    <div>
                        <InputLabel value="Company" for="company" />
                        <TextInput
                            id="company"
                            v-model="form.company"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.company"
                        />
                    </div>

                    <div>
                        <InputLabel value="Status" for="status" />
                        <SelectInput
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full"
                        >
                            <option value="active">Active</option>
                            <option value="former">Former</option>
                            <option value="blacklisted">Blacklisted</option>
                            <option value="archived">Archived</option>
                        </SelectInput>
                        <InputError
                            class="mt-2"
                            :message="form.errors.status"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel value="Address" for="address" />
                        <TextAreaInput
                            id="address"
                            v-model="form.address"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.address"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel value="Notes" for="notes" />
                        <TextAreaInput
                            id="notes"
                            v-model="form.notes"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.notes" />
                    </div>

                    <label
                        class="flex items-center gap-2 text-sm text-gray-700"
                    >
                        <input
                            v-model="form.is_vip"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        VIP guest
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('guests.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton type="submit" :disabled="form.processing">
                    {{ form.processing ? "Saving…" : "Update Guest" }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
