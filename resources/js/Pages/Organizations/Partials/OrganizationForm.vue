<script setup>
import { useForm } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    organization: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    name: props.organization?.name ?? "",
    code: props.organization?.code ?? "",
    description: props.organization?.description ?? "",
    address: props.organization?.address ?? "",
    city: props.organization?.city ?? "",
    state: props.organization?.state ?? "",
    country: props.organization?.country ?? "Nigeria",
    postal_code: props.organization?.postal_code ?? "",
    phone: props.organization?.phone ?? "",
    email: props.organization?.email ?? "",
    currency: props.organization?.currency ?? "NGN",
    star_rating: props.organization?.star_rating ?? 3,
    check_in_time: props.organization?.check_in_time ?? "14:00",
    check_out_time: props.organization?.check_out_time ?? "12:00",
    status: props.organization?.status ?? "active",
});

const submit = () => {
    if (props.organization) {
        form.put(route("organizations.update", props.organization.id), {
            preserveScroll: true,
        });
    } else {
        form.post(route("organizations.store"), {
            preserveScroll: true,
        });
    }
};

const cancel = () => {
    window.history.back();
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">
                Basic Information
            </h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Core details for the parent company.
            </p>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel value="Organization Name" />
                    <TextInput
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. Heritage Hotels Group"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel value="Organization Code" />
                    <TextInput
                        v-model="form.code"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. HHG"
                        maxlength="20"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.code" />
                </div>

                <div>
                    <InputLabel value="Currency" />
                    <SelectInput
                        v-model="form.currency"
                        class="mt-1 block w-full"
                    >
                        <option value="NGN">NGN — Nigerian Naira</option>
                        <option value="USD">USD — US Dollar</option>
                        <option value="GBP">GBP — British Pound</option>
                        <option value="EUR">EUR — Euro</option>
                    </SelectInput>
                    <InputError class="mt-2" :message="form.errors.currency" />
                </div>

                <div>
                    <InputLabel value="Star Rating" />
                    <SelectInput
                        v-model.number="form.star_rating"
                        class="mt-1 block w-full"
                    >
                        <option v-for="n in 7" :key="n" :value="n">
                            {{ n }} star{{ n > 1 ? "s" : "" }}
                        </option>
                    </SelectInput>
                    <InputError
                        class="mt-2"
                        :message="form.errors.star_rating"
                    />
                </div>

                <div>
                    <InputLabel value="Status" />
                    <SelectInput
                        v-model="form.status"
                        class="mt-1 block w-full"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </SelectInput>
                    <InputError class="mt-2" :message="form.errors.status" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel value="Description" />
                    <TextAreaInput
                        v-model="form.description"
                        class="mt-1 block w-full"
                        placeholder="A short description of the organization..."
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.description"
                    />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">Location</h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Registered address of the organization.
            </p>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel value="Street Address" />
                    <TextInput
                        v-model="form.address"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. 28 Folagbade Street"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.address" />
                </div>

                <div>
                    <InputLabel value="City" />
                    <TextInput
                        v-model="form.city"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. Ijebu Ode"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.city" />
                </div>

                <div>
                    <InputLabel value="State / Province" />
                    <TextInput
                        v-model="form.state"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. Ogun State"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.state" />
                </div>

                <div>
                    <InputLabel value="Country" />
                    <TextInput
                        v-model="form.country"
                        type="text"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.country" />
                </div>

                <div>
                    <InputLabel value="Postal Code" />
                    <TextInput
                        v-model="form.postal_code"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.postal_code"
                    />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">
                Contact & Check-in Details
            </h3>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="Phone" />
                    <TextInput
                        v-model="form.phone"
                        type="tel"
                        class="mt-1 block w-full"
                        placeholder="+234 800 000 0000"
                    />
                    <InputError class="mt-2" :message="form.errors.phone" />
                </div>

                <div>
                    <InputLabel value="Email" />
                    <TextInput
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        placeholder="info@example.com"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

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

        <div class="flex items-center justify-end gap-3">
            <SecondaryButton type="button" @click="cancel">
                Cancel
            </SecondaryButton>
            <PrimaryButton type="submit" :disabled="form.processing">
                {{
                    form.processing
                        ? "Saving..."
                        : props.organization
                          ? "Update Organization"
                          : "Create Organization"
                }}
            </PrimaryButton>
        </div>
    </form>
</template>
