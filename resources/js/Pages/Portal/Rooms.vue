<script setup>
import { ref } from "vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import CustomerLayout from "@/Layouts/CustomerLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    guest: Object,
    rooms: Array,
    nights: Number,
    filters: Object,
});

const page = usePage();
const selected = ref(null);

const search = ref({
    check_in: props.filters.check_in,
    check_out: props.filters.check_out,
});

const form = useForm({
    room_id: "",
    check_in: props.filters.check_in,
    check_out: props.filters.check_out,
    adults: 2,
    children: 0,
    special_requests: "",
});

const searchRooms = () => {
    router.get(
        route("portal.rooms", {
            check_in: search.value.check_in,
            check_out: search.value.check_out,
        }),
        {},
        { preserveScroll: true },
    );
};

const openBooking = (room) => {
    selected.value = room;
    form.room_id = room.id;
    form.check_in = search.value.check_in;
    form.check_out = search.value.check_out;
    form.clearErrors();
};

const cancelBooking = () => {
    selected.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post(route("portal.reservations.store"), {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = null;
            form.reset();
        },
    });
};

const formatMoney = (amount) => `₦${Number(amount ?? 0).toLocaleString()}`;
</script>

<template>
    <Head title="Available Rooms" />

    <CustomerLayout>
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">
                    Available Rooms
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Pick your dates, choose a room and confirm your stay.
                </p>
            </div>

            <div
                v-if="page.props.flash?.success"
                class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            >
                {{ page.props.flash.success }}
            </div>

            <!-- Date search -->
            <div
                class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
            >
                <form
                    class="flex flex-col gap-4 sm:flex-row sm:items-end"
                    @submit.prevent="searchRooms"
                >
                    <div class="flex-1">
                        <InputLabel value="Check-in" />
                        <TextInput
                            v-model="search.check_in"
                            type="date"
                            class="mt-1 block w-full"
                        />
                    </div>
                    <div class="flex-1">
                        <InputLabel value="Check-out" />
                        <TextInput
                            v-model="search.check_out"
                            type="date"
                            class="mt-1 block w-full"
                        />
                    </div>
                    <PrimaryButton type="submit">Search</PrimaryButton>
                </form>
                <p class="mt-3 text-xs text-gray-500">
                    Showing rooms free for
                    <span class="font-semibold text-gray-700"
                        >{{ nights }} night{{ nights > 1 ? "s" : "" }}</span
                    >
                    ({{ filters.check_in }} → {{ filters.check_out }}).
                </p>
            </div>

            <!-- Guest profile notice -->
            <div
                v-if="!guest"
                class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
            >
                Your account is not linked to a guest profile yet, so booking is
                disabled. Contact reception to get linked — you can still browse
                available rooms below.
            </div>

            <div
                v-if="form.errors.room_id"
                class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"
            >
                {{ form.errors.room_id }}
            </div>

            <!-- Booking panel -->
            <div
                v-if="selected"
                class="mb-6 rounded-xl border border-indigo-200 bg-white p-6 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-indigo-600">
                            Booking Room {{ selected.room_number }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ selected.room_type?.name }} · Floor
                            {{ selected.floor }} · Up to
                            {{ selected.capacity }} guests
                        </p>
                    </div>
                    <div class="text-end">
                        <p class="text-lg font-semibold text-gray-900">
                            {{ formatMoney(selected.total) }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ formatMoney(selected.price) }} ×
                            {{ nights }} night{{ nights > 1 ? "s" : "" }}
                        </p>
                    </div>
                </div>

                <form
                    class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3"
                    @submit.prevent="submit"
                >
                    <div>
                        <InputLabel value="Adults" />
                        <TextInput
                            v-model.number="form.adults"
                            type="number"
                            min="1"
                            :max="selected.capacity"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.adults"
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
                        <InputError
                            class="mt-2"
                            :message="form.errors.children"
                        />
                    </div>
                    <div>
                        <InputLabel value="Check-in / Check-out" />
                        <p class="mt-2 text-sm text-gray-700">
                            {{ form.check_in }} → {{ form.check_out }}
                        </p>
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel value="Special requests (optional)" />
                        <TextAreaInput
                            v-model="form.special_requests"
                            class="mt-1 block w-full"
                            rows="2"
                            placeholder="Late arrival, extra pillows, quiet room…"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.special_requests"
                        />
                    </div>
                    <div class="flex justify-end gap-3 sm:col-span-3">
                        <SecondaryButton type="button" @click="cancelBooking">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            :disabled="form.processing || !guest"
                        >
                            {{
                                form.processing
                                    ? "Booking…"
                                    : "Confirm Reservation"
                            }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Room list -->
            <div
                v-if="rooms.length"
                class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="room in rooms"
                    :key="room.id"
                    class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition"
                    :class="
                        selected?.id === room.id
                            ? 'border-indigo-300 ring-1 ring-indigo-200'
                            : 'hover:border-indigo-200 hover:shadow-md'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-base font-semibold text-gray-900">
                                {{ room.room_type?.name ?? "Room" }}
                            </p>
                            <p class="mt-0.5 text-sm text-gray-500">
                                Room {{ room.room_number }} · Floor
                                {{ room.floor || "—" }}
                            </p>
                        </div>
                        <span
                            class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700"
                        >
                            Available
                        </span>
                    </div>

                    <p
                        v-if="room.description"
                        class="mt-3 text-sm text-gray-500"
                    >
                        {{ room.description }}
                    </p>

                    <dl
                        class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-4 text-sm"
                    >
                        <div>
                            <dt class="text-xs text-gray-400">Sleeps</dt>
                            <dd class="font-medium text-gray-900">
                                {{ room.capacity }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400">Per night</dt>
                            <dd class="font-medium text-gray-900">
                                {{ formatMoney(room.price) }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-900">
                            {{ formatMoney(room.total) }}
                            <span class="text-xs font-normal text-gray-500"
                                >total</span
                            >
                        </p>
                        <PrimaryButton
                            type="button"
                            :disabled="!guest"
                            @click="openBooking(room)"
                        >
                            {{ selected?.id === room.id ? "Selected" : "Book" }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center"
            >
                <AppIcon
                    name="bed"
                    classes="[&>svg]:h-10 [&>svg]:w-10 mx-auto text-gray-300"
                />
                <p class="mt-3 text-sm font-medium text-gray-900">
                    No rooms available for these dates
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    Try different dates or contact reception for assistance.
                </p>
            </div>
        </div>
    </CustomerLayout>
</template>
