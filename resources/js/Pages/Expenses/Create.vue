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
    description: "",
    amount: "",
    category: "",
    expense_date: new Date().toISOString().split("T")[0],
    reference: "",
    notes: "",
});

function store() {
    form.post(route("expenses.store"));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Record Expense
            </h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form
                    @submit.prevent="store"
                    class="rounded-lg bg-white p-8 shadow-sm"
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <InputLabel for="description" value="Description" />
                            <TextInput
                                id="description"
                                v-model="form.description"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.description"
                            />
                        </div>
                        <div>
                            <InputLabel for="amount" value="Amount (NGN)" />
                            <TextInput
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.amount"
                            />
                        </div>
                        <div>
                            <InputLabel for="category" value="Category" />
                            <select
                                id="category"
                                v-model="form.category"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select category...</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Supplies">Supplies</option>
                                <option value="Staff Welfare">
                                    Staff Welfare
                                </option>
                                <option value="Marketing">Marketing</option>
                                <option value="Cleaning">Cleaning</option>
                                <option value="Other">Other</option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.category"
                            />
                        </div>
                        <div>
                            <InputLabel for="expense_date" value="Date" />
                            <TextInput
                                id="expense_date"
                                v-model="form.expense_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.expense_date"
                            />
                        </div>
                        <div>
                            <InputLabel for="reference" value="Reference" />
                            <TextInput
                                id="reference"
                                v-model="form.reference"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.reference"
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
                            >Save Expense</PrimaryButton
                        >
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
