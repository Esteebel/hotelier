<script setup>
import { computed } from "vue";
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
    invoice: { type: Object, required: true },
    guests: { type: Array, default: () => [] },
    reservations: { type: Array, default: () => [] },
    activeBranch: { type: Object, default: null },
});

const form = useForm({
    guest_id: props.invoice.guest_id ?? "",
    reservation_id: props.invoice.reservation_id ?? "",
    invoice_number: props.invoice.invoice_number ?? "",
    issued_on: props.invoice.issued_on ?? "",
    due_on: props.invoice.due_on ?? "",
    subtotal: props.invoice.subtotal ?? "0",
    tax: props.invoice.tax ?? "0",
    discount: props.invoice.discount ?? "0",
    total: props.invoice.total ?? "0",
    notes: props.invoice.notes ?? "",
    status: props.invoice.status ?? "draft",
});

const computedTotal = computed(() => {
    const sub = Number(form.subtotal) || 0;
    const tax = Number(form.tax) || 0;
    const disc = Number(form.discount) || 0;
    return Math.max(0, sub + tax - disc).toFixed(2);
});

const submit = () => {
    form.total = computedTotal.value;
    form.put(route("invoices.update", props.invoice.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit ${invoice.invoice_number ?? 'Invoice'}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit Invoice
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
                        <InputLabel value="Invoice Number" />
                        <TextInput
                            v-model="form.invoice_number"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.invoice_number"
                        />
                    </div>

                    <div>
                        <InputLabel value="Status" />
                        <SelectInput
                            v-model="form.status"
                            class="mt-1 block w-full"
                        >
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="partially_paid">
                                Partially Paid
                            </option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="void">Void</option>
                        </SelectInput>
                        <InputError
                            class="mt-2"
                            :message="form.errors.status"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel value="Issued On" />
                            <TextInput
                                v-model="form.issued_on"
                                type="date"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.issued_on"
                            />
                        </div>
                        <div>
                            <InputLabel value="Due On" />
                            <TextInput
                                v-model="form.due_on"
                                type="date"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.due_on"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Subtotal" />
                        <TextInput
                            v-model="form.subtotal"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.subtotal"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel value="Tax" />
                            <TextInput
                                v-model="form.tax"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel value="Discount" />
                            <TextInput
                                v-model="form.discount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Total (computed)" />
                        <TextInput
                            :value="computedTotal"
                            type="text"
                            class="mt-1 block w-full bg-gray-50"
                            readonly
                        />
                        <InputError class="mt-2" :message="form.errors.total" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel value="Notes" />
                        <TextAreaInput
                            v-model="form.notes"
                            class="mt-1 block w-full"
                            placeholder="Invoice notes…"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('invoices.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton type="submit" :disabled="form.processing">
                    {{ form.processing ? "Saving…" : "Update Invoice" }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
