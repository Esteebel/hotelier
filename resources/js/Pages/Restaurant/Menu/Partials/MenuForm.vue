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
    menuItem: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.menuItem?.name ?? "",
    category: props.menuItem?.category ?? "",
    description: props.menuItem?.description ?? "",
    price: props.menuItem?.price ?? "",
    is_available: props.menuItem?.is_available ?? true,
    sort_order: props.menuItem?.sort_order ?? 0,
});

const submit = () => {
    if (props.menuItem) {
        form.put(route("menu.update", props.menuItem.id), {
            preserveScroll: true,
        });
    } else {
        form.post(route("menu.store"), {
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
            <h3 class="text-sm font-semibold text-gray-900">Item Details</h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Food or drink guests can order from this menu.
            </p>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel value="Name" />
                    <TextInput
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. Jollof Rice & Chicken"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel value="Category" />
                    <SelectInput
                        v-model="form.category"
                        class="mt-1 block w-full"
                        required
                    >
                        <option value="" disabled>Select category…</option>
                        <option
                            v-for="category in categories"
                            :key="category"
                            :value="category"
                        >
                            {{ category }}
                        </option>
                    </SelectInput>
                    <InputError class="mt-2" :message="form.errors.category" />
                </div>

                <div>
                    <InputLabel value="Price (NGN)" />
                    <TextInput
                        v-model="form.price"
                        type="number"
                        step="0.01"
                        min="0"
                        class="mt-1 block w-full"
                        placeholder="0.00"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.price" />
                </div>

                <div>
                    <InputLabel value="Availability" />
                    <SelectInput
                        v-model="form.is_available"
                        class="mt-1 block w-full"
                    >
                        <option :value="true">Available</option>
                        <option :value="false">Unavailable</option>
                    </SelectInput>
                    <InputError
                        class="mt-2"
                        :message="form.errors.is_available"
                    />
                </div>

                <div>
                    <InputLabel value="Sort Order" />
                    <TextInput
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        class="mt-1 block w-full"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.sort_order"
                    />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel value="Description" />
                    <TextAreaInput
                        v-model="form.description"
                        class="mt-1 block w-full"
                        rows="3"
                        placeholder="A short description of the dish…"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.description"
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
                        : props.menuItem
                          ? "Update Item"
                          : "Create Item"
                }}
            </PrimaryButton>
        </div>
    </form>
</template>
