<script setup>
import { onMounted, ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    rooms: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    activeBranch: {
        type: Object,
        default: null,
    },
});

const search = ref(props.filters.search ?? "");
const status = ref(props.filters.status ?? "");
const roomToDelete = ref(null);
const deleting = useForm({});

const currencyFormatter = new Intl.NumberFormat("en-NG", {
    style: "currency",
    currency: "NGN",
});

const formatPrice = (price) => {
    if (price === null || price === undefined || price === "") return "—";
    return currencyFormatter.format(price);
};

const reload = (preserveKey = false) => {
    router.get(
        route("rooms.index"),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["rooms"],
            onSuccess: () => {
                if (preserveKey) search.value = props.filters.search ?? "";
            },
        },
    );
};

let searchTimer;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => reload(), 300);
});

watch(status, () => reload());

onMounted(() => {
    if (props.filters.search) {
        search.value = props.filters.search;
    }
});

const confirmDelete = (room) => {
    roomToDelete.value = room;
};

const destroyRoom = () => {
    deleting.delete(route("rooms.destroy", roomToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            roomToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Rooms" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-1 items-center justify-between gap-4">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-900"
                    >
                        Rooms
                    </h2>
                    <p class="text-sm text-gray-500">
                        Manage rooms for
                        {{
                            activeBranch
                                ? activeBranch.name
                                : "your active branch"
                        }}.
                    </p>
                </div>
                <Link
                    :href="route('rooms.create')"
                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                >
                    <AppIcon name="plus" classes="[&>svg]:h-4 [&>svg]:w-4" />
                    Add Room
                </Link>
            </div>
        </template>

        <div
            v-if="$page.props.flash?.success"
            class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
            {{ $page.props.flash.success }}
        </div>

        <div class="mb-4 flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1 sm:max-w-xs">
                <span
                    class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"
                >
                    <AppIcon name="search" classes="[&>svg]:h-4 [&>svg]:w-4" />
                </span>
                <TextInput
                    v-model="search"
                    type="search"
                    placeholder="Search rooms..."
                    class="w-full pl-9"
                />
            </div>
            <SelectInput v-model="status" class="sm:w-44">
                <option value="">All statuses</option>
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="reserved">Reserved</option>
                <option value="dirty">Dirty</option>
                <option value="cleaning">Cleaning</option>
                <option value="maintenance">Maintenance</option>
                <option value="out_of_service">Out of Service</option>
            </SelectInput>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Room
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Floor
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Type
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Rate
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Status
                            </th>
                            <th scope="col" class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="room in rooms.data" :key="room.id">
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"
                                    >
                                        <AppIcon
                                            name="bed"
                                            classes="[&>svg]:h-5 [&>svg]:w-5"
                                        />
                                    </span>
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ room.room_number }}
                                        </p>
                                        <p
                                            v-if="room.capacity"
                                            class="text-xs text-gray-500"
                                        >
                                            Cap. {{ room.capacity }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ room.floor ?? "—" }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ room.room_type?.name ?? "—" }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ formatPrice(room.price) }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="room.badge_class"
                                >
                                    {{ room.status_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="route('rooms.edit', room.id)"
                                        class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                                        title="Edit"
                                    >
                                        <AppIcon
                                            name="settings"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </Link>
                                    <button
                                        type="button"
                                        class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600"
                                        title="Delete"
                                        @click="confirmDelete(room)"
                                    >
                                        <AppIcon
                                            name="x"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!rooms.data.length">
                            <td colspan="6" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">
                                    No rooms found
                                </p>
                                <p class="mt-1 text-sm text-gray-400">
                                    {{
                                        search || status
                                            ? "Try adjusting your filters."
                                            : "Get started by adding your first room."
                                    }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="rooms.total > rooms.per_page"
                class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3"
            >
                <p class="text-xs text-gray-500">
                    Showing
                    <span class="font-medium">{{ rooms.from }}</span>
                    to
                    <span class="font-medium">{{ rooms.to }}</span>
                    of
                    <span class="font-medium">{{ rooms.total }}</span>
                    rooms
                </p>
                <nav class="flex items-center gap-1">
                    <template v-for="link in rooms.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="rounded-md px-2.5 py-1 text-sm"
                            :class="
                                link.active
                                    ? 'bg-indigo-600 font-medium text-white'
                                    : 'text-gray-600 hover:bg-gray-100'
                            "
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-2.5 py-1 text-sm text-gray-400"
                            v-html="link.label"
                        ></span>
                    </template>
                </nav>
            </div>
        </div>

        <Modal :show="!!roomToDelete" @close="roomToDelete = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete Room {{ roomToDelete?.room_number }}?
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    This will permanently remove this room record. This action
                    cannot be undone.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="roomToDelete = null">
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        :disabled="deleting.processing"
                        @click="destroyRoom"
                    >
                        {{
                            deleting.processing ? "Deleting..." : "Delete Room"
                        }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
