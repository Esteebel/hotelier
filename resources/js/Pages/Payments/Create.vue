<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    guests: { type: Array, default: () => [] },
    reservations: { type: Array, default: () => [] },
    activeBranch: { type: Object, default: null },
});

const form = useForm({
    guest_id: "",
    reservation_id: "",
    method: "cash",
    amount: "",
    currency: "NGN",
    paid_on: "",
    reference: "",
    notes: "",
    status: "pending",
});

const submit = () => {
    form.post(route("payments.store"), { preserveScroll: true });
};
</script>

<template>
    <Head title="Record Payment" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2>Record Payment</h2>
                <p>
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
                                {{ r.reservation_code }}
                            </option>
                        </SelectInput>
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
                            <option value="credit">Credit Account</option>
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
                    {{ form.processing ? "Saving…" : "Record Payment" }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
