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
    room: {
        type: Object,
        default: null,
    },
    roomTypes: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    room_type_id: props.room?.room_type_id ?? "",
    room_number: props.room?.room_number ?? "",
    floor: props.room?.floor ?? "",
    capacity: props.room?.capacity ?? 1,
    price: props.room?.price ?? "",
    status: props.room?.status ?? "available",
    description: props.room?.description ?? "",
    is_active: props.room?.is_active ?? true,
});

const submit = () => {
    if (props.room) {
        form.put(route("rooms.update", props.room.id), {
            preserveScroll: true,
        });
    } else {
        form.post(route("rooms.store"), {
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
            <h3 class="text-sm font-semibold text-gray-900">Room Details</h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Core information for this room.
            </p>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="Room Type" />
                    <SelectInput
                        v-model.number="form.room_type_id"
                        class="mt-1 block w-full"
                        required
                    >
                        <option value="" disabled>Select a room type</option>
                        <option
                            v-for="type in roomTypes"
                            :key="type.id"
                            :value="type.id"
                        >
                            {{ type.name }}
                        </option>
                    </SelectInput>
                    <InputError
                        class="mt-2"
                        :message="form.errors.room_type_id"
                    />
                </div>

                <div>
                    <InputLabel value="Room Number" />
                    <TextInput
                        v-model="form.room_number"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. 101"
                        maxlength="20"
                        required
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.room_number"
                    />
                </div>

                <div>
                    <InputLabel value="Floor" />
                    <TextInput
                        v-model="form.floor"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. 1, Ground, B2"
                        maxlength="20"
                    />
                    <InputError class="mt-2" :message="form.errors.floor" />
                </div>

                <div>
                    <InputLabel value="Capacity" />
                    <TextInput
                        v-model.number="form.capacity"
                        type="number"
                        class="mt-1 block w-full"
                        min="1"
                        max="50"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.capacity" />
                </div>

                <div>
                    <InputLabel value="Rate (₦ / night)" />
                    <TextInput
                        v-model="form.price"
                        type="number"
                        class="mt-1 block w-full"
                        step="0.01"
                        min="0"
                        placeholder="Leave blank to use type base price"
                    />
                    <InputError class="mt-2" :message="form.errors.price" />
                </div>

                <div>
                    <InputLabel value="Status" />
                    <SelectInput
                        v-model="form.status"
                        class="mt-1 block w-full"
                        required
                    >
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="reserved">Reserved</option>
                        <option value="dirty">Dirty</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="out_of_service">Out of Service</option>
                    </SelectInput>
                    <InputError class="mt-2" :message="form.errors.status" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel value="Description" />
                    <TextAreaInput
                        v-model="form.description"
                        class="mt-1 block w-full"
                        placeholder="Optional notes about the room..."
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.description"
                    />
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
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
                        : props.room
                          ? "Update Room"
                          : "Create Room"
                }}
            </PrimaryButton>
        </div>
    </form>
</template>
