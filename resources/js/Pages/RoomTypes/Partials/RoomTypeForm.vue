<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    roomType: {
        type: Object,
        default: null,
    },
});

const { props: pageProps } = usePage();

const form = useForm({
    branch_id: props.roomType?.branch_id ?? pageProps.activeBranch?.id ?? "",
    name: props.roomType?.name ?? "",
    description: props.roomType?.description ?? "",
    base_price: props.roomType?.base_price ?? "",
    capacity: props.roomType?.capacity ?? 1,
    number_of_beds: props.roomType?.number_of_beds ?? 1,
    bed_configuration: props.roomType?.bed_configuration ?? "",
    is_active: props.roomType?.is_active ?? true,
});

const submit = () => {
    if (props.roomType) {
        form.put(route("room-types.update", props.roomType.id), {
            preserveScroll: true,
        });
    } else {
        form.post(route("room-types.store"), {
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
        <input type="hidden" name="branch_id" :value="form.branch_id" />
        <InputError :message="form.errors.branch_id" />

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">
                Room Type Details
            </h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Define the room category and its pricing.
            </p>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel value="Room Type Name" />
                    <TextInput
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. Deluxe King Room"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel value="Base Price" />
                    <TextInput
                        v-model.number="form.base_price"
                        type="number"
                        step="0.01"
                        min="0"
                        class="mt-1 block w-full"
                        placeholder="e.g. 25000"
                        required
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.base_price"
                    />
                </div>

                <div>
                    <InputLabel value="Bed Configuration" />
                    <TextInput
                        v-model="form.bed_configuration"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. 1 King Bed"
                        maxlength="50"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.bed_configuration"
                    />
                </div>

                <div>
                    <InputLabel value="Capacity" />
                    <TextInput
                        v-model.number="form.capacity"
                        type="number"
                        step="1"
                        min="1"
                        max="50"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.capacity" />
                </div>

                <div>
                    <InputLabel value="Number of Beds" />
                    <TextInput
                        v-model.number="form.number_of_beds"
                        type="number"
                        step="1"
                        min="1"
                        max="10"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.number_of_beds"
                    />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel value="Description" />
                    <TextAreaInput
                        v-model="form.description"
                        class="mt-1 block w-full"
                        placeholder="A short description of this room type..."
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.description"
                    />
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-center gap-3">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-sm font-medium text-gray-700">
                            Active — available for bookings
                        </span>
                    </label>
                    <InputError class="mt-2" :message="form.errors.is_active" />
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
                        : props.roomType
                          ? "Update Room Type"
                          : "Create Room Type"
                }}
            </PrimaryButton>
        </div>
    </form>
</template>
