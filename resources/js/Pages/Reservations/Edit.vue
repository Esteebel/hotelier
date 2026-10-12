<script setup>
import { Head, useForm, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import TextInput from "@/Components/TextInput.vue";
import SelectInput from "@/Components/SelectInput.vue";

const props = defineProps({
    reservation: { type: Object, required: true },
    guests: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    activeBranch: { type: Object, default: null },
});

const form = useForm({
    guest_id: props.reservation.guest_id,
    room_id: props.reservation.room_id,
    check_in: props.reservation.check_in,
    check_out: props.reservation.check_out,
    adults: props.reservation.adults,
    children: props.reservation.children,
    notes: props.reservation.notes,
    status: props.reservation.status,
    source: props.reservation.source ?? "direct",
});

const submit = () => {
    form.put(route("reservations.update", props.reservation.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit Reservation ${reservation.code}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Edit Reservation
                </h2>
                <p class="text-sm text-gray-500 font-mono">
                    {{ reservation.code }}
                </p>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
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
                        <InputLabel value="Room" />
                        <SelectInput
                            v-model="form.room_id"
                            class="mt-1 block w-full"
                        >
                            <option value="">No room (on request)</option>
                            <option
                                v-for="room in rooms"
                                :key="room.id"
                                :value="room.id"
                            >
                                {{ room.room_number }}
                            </option>
                        </SelectInput>
                        <InputError
                            class="mt-2"
                            :message="form.errors.room_id"
                        />
                    </div>

                    <div>
                        <InputLabel value="Status" />
                        <SelectInput
                            v-model="form.status"
                            class="mt-1 block w-full"
                        >
                            <option value="confirmed">Confirmed</option>
                            <option value="checked_in">Checked In</option>
                            <option value="checked_out">Checked Out</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no_show">No Show</option>
                        </SelectInput>
                    </div>

                    <div>
                        <InputLabel value="Source" />
                        <SelectInput
                            v-model="form.source"
                            class="mt-1 block w-full"
                        >
                            <option value="direct">Direct</option>
                            <option value="phone">Phone</option>
                            <option value="website">Website</option>
                            <option value="walk_in">Walk-in</option>
                            <option value="email">Email</option>
                            <option value="referral">Referral</option>
                            <option value="ota">OTA / Booking site</option>
                        </SelectInput>
                        <InputError
                            class="mt-2"
                            :message="form.errors.source"
                        />
                    </div>

                    <div>
                        <InputLabel value="Check-In" />
                        <TextInput
                            v-model="form.check_in"
                            type="date"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.check_in"
                        />
                    </div>

                    <div>
                        <InputLabel value="Check-Out" />
                        <TextInput
                            v-model="form.check_out"
                            type="date"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.check_out"
                        />
                    </div>

                    <div>
                        <InputLabel value="Adults" />
                        <TextInput
                            v-model.number="form.adults"
                            type="number"
                            min="1"
                            class="mt-1 block w-full"
                        />
                    </div>

                    <div>
                        <InputLabel value="Children" />
                        <TextInput
                            v-model.number="form.children"
                            type="number"
                            min="0"
                            class="mt-1 block w-full"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel value="Notes" />
                        <TextAreaInput
                            v-model="form.notes"
                            class="mt-1 block w-full"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('reservations.index')">
                    <SecondaryButton type="button">Cancel</SecondaryButton>
                </Link>
                <PrimaryButton type="submit" :disabled="form.processing">
                    {{ form.processing ? "Saving…" : "Update Reservation" }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
