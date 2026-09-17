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
    branch: {
        type: Object,
        default: null,
    },
    organizations: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    organization_id: props.branch?.organization_id ?? "",
    name: props.branch?.name ?? "",
    code: props.branch?.code ?? "",
    description: props.branch?.description ?? "",
    address: props.branch?.address ?? "",
    city: props.branch?.city ?? "",
    state: props.branch?.state ?? "",
    country: props.branch?.country ?? "Nigeria",
    postal_code: props.branch?.postal_code ?? "",
    phone: props.branch?.phone ?? "",
    email: props.branch?.email ?? "",
    currency: props.branch?.currency ?? "NGN",
    star_rating: props.branch?.star_rating ?? 1,
    check_in_time: props.branch?.check_in_time ?? "14:00",
    check_out_time: props.branch?.check_out_time ?? "12:00",
    status: props.branch?.status ?? "active",
    is_default: props.branch?.is_default ?? false,
});

const submit = () => {
    if (props.branch) {
        form.put(route("branches.update", props.branch.id), {
            preserveScroll: true,
        });
    } else {
        form.post(route("branches.store"), {
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
        <!-- Basic information -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">
                Basic Information
            </h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Core details used across the branch.
            </p>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel value="Organization" />
                    <SelectInput
                        v-model="form.organization_id"
                        class="mt-1 block w-full"
                        required
                    >
                        <option value="" disabled>Select organization</option>
                        <option
                            v-for="org in organizations"
                            :key="org.id"
                            :value="org.id"
                        >
                            {{ org.name }}
                        </option>
                    </SelectInput>
                    <InputError
                        class="mt-2"
                        :message="form.errors.organization_id"
                    />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel value="Branch Name" />
                    <TextInput
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. Ijebu Heritage Branch"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel value="Branch Code" />
                    <TextInput
                        v-model="form.code"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. IHH-B1"
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
                        placeholder="A short description of the branch..."
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.description"
                    />
                </div>
            </div>
        </div>

        <!-- Location -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">Location</h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Physical address of the branch.
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

        <!-- Contact & check-in details -->
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

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
            <SecondaryButton type="button" @click="cancel">
                Cancel
            </SecondaryButton>
            <PrimaryButton type="submit" :disabled="form.processing">
                {{
                    form.processing
                        ? "Saving..."
                        : props.branch
                          ? "Update Branch"
                          : "Create Branch"
                }}
            </PrimaryButton>
        </div>
    </form>
</template>
