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
    payment: { type: Object, required: true },
    guests: { type: Array, default: () => [] },
    reservations: { type: Array, default: () => [] },
    invoices: { type: Array, default: () => [] },
    activeBranch: { type: Object, default: null },
});

const form = useForm({
    guest_id: props.payment.guest_id ?? "",
    reservation_id: props.payment.reservation_id ?? "",
    invoice_id: props.payment.invoice_id ?? "",
    payment_number: props.payment.payment_number ?? "",
    method: props.payment.method ?? "cash",
    amount: props.payment.amount ?? "",
    paid_on: props.payment.paid_on ?? "",
    reference: props.payment.reference ?? "",
    notes: props.payment.notes ?? "",
    status: props.payment.status ?? "completed",
});

const submit = () => {
    form.put(route("payments.update", props.payment.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Edit Payment" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit Payment
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
                        <InputLabel value="Guest" />
                        <SelectInput
                            v-model="form.guest_id"
                            class="mt-1 block w-full"
                            required
                        >
                            <option value="">Select a guest…</option>
                            <option
                                v-for="guest in guests"
                                :key="guest.id"
                                :value="guest.id"
                            >
                                {{ guest.full_name }}
                            </option>
                        </SelectInput>
                        <InputError
                            class="mt-2"
                            :message="form.errors.guest_id"
                        />
                    </div>

                    <div>
                        <InputLabel value="Reservation" />
                        <SelectInput
                            v-model="form.reservation_id"
                            class="mt-1 block w-full"
                        >
                            <option value="">No reservation</option>
                            <option
                                v-for="r in reservations"
                                :key="r.id"
                                :value="r.id"
                            >
                                {{ r.reservation_number }}
                            </option>
                        </SelectInput>
                    </div>

                    <div>
                        <InputLabel value="Invoice" />
                        <SelectInput
                            v-model="form.invoice_id"
                            class="mt-1 block w-full"
                        >
                            <option value="">No invoice</option>
                            <option
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                :value="invoice.id"
                            >
                                {{ invoice.invoice_number }}
                            </option>
                        </SelectInput>
                        <InputError
                            class="mt-2"
                            :message="form.errors.invoice_id"
                        />
                    </div>

                    <div>
                        <InputLabel value="Payment Number" />
                        <TextInput
                            v-model="form.payment_number"
                            type="text"
                            class="mt-1 block w-full"
                            readonly
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.payment_number"
                        />
                    </div>

                    <div>
                        <InputLabel value="Payment Method" />
                        <SelectInput
                            v-model="form.method"
                            class="mt-1 block w-full"
                        >
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="cheque">Cheque</option>
                            <option value="credit_account">
                                Credit Account
                            </option>
                        </SelectInput>
                        <InputError
                            class="mt-2"
                            :message="form.errors.method"
                        />
                    </div>

                    <div>
                        <InputLabel value="Amount" />
                        <TextInput
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.amount"
                        />
                    </div>

                    <div>
                        <InputLabel value="Reference" />
                        <TextInput
                            v-model="form.reference"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g. TNG-2025-0001"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.reference"
                        />
                    </div>

                    <div>
                        <InputLabel value="Paid On" />
                        <TextInput
                            v-model="form.paid_on"
                            type="date"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.paid_on"
                        />
                    </div>

                    <div>
                        <InputLabel value="Status" />
                        <SelectInput
                            v-model="form.status"
                            class="mt-1 block w-full"
                        >
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                            <option value="void">Void</option>
                        </SelectInput>
                        <InputError
                            class="mt-2"
                            :message="form.errors.status"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel value="Notes" />
                        <TextAreaInput
                            v-model="form.notes"
                            class="mt-1 block w-full"
                            placeholder="Payment notes…"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('payments.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton type="submit" :disabled="form.processing">
                    {{ form.processing ? "Saving…" : "Update Payment" }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
