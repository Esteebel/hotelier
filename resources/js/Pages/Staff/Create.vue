<script setup>
import { useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({ activeBranch: Object });

const form = useForm({
    first_name: "",
    last_name: "",
    position: "",
    department: "",
    email: "",
    phone: "",
    status: "active",
    notes: "",
});

function store() {
    form.post(route("staff.store"));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Add Staff Member
            </h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form
                    @submit.prevent="store"
                    class="rounded-lg bg-white p-8 shadow-sm"
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="first_name" value="First Name" />
                            <TextInput
                                id="first_name"
                                v-model="form.first_name"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.first_name"
                            />
                        </div>
                        <div>
                            <InputLabel for="last_name" value="Last Name" />
                            <TextInput
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.last_name"
                            />
                        </div>
                        <div>
                            <InputLabel for="position" value="Position" />
                            <TextInput
                                id="position"
                                v-model="form.position"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.position"
                            />
                        </div>
                        <div>
                            <InputLabel for="department" value="Department" />
                            <select
                                id="department"
                                v-model="form.department"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select department...</option>
                                <option value="Reception">Reception</option>
                                <option value="Housekeeping">
                                    Housekeeping
                                </option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Finance">Finance</option>
                                <option value="Front Office">
                                    Front Office
                                </option>
                                <option value="Food & Beverage">
                                    Food & Beverage
                                </option>
                                <option value="Management">Management</option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.department"
                            />
                        </div>
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.email"
                            />
                        </div>
                        <div>
                            <InputLabel for="phone" value="Phone" />
                            <TextInput
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.phone"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="notes" value="Notes" />
                            <TextAreaInput
                                id="notes"
                                v-model="form.notes"
                                class="mt-1 block w-full"
                                rows="3"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.notes"
                            />
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-end gap-4">
                        <PrimaryButton :disabled="form.processing"
                            >Save Staff</PrimaryButton
                        >
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
