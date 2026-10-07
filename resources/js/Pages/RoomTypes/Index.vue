<script setup>
import { onMounted, ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { usePermissions } from "@/Composables/usePermissions";

const props = defineProps({
    roomTypes: {
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
const roomTypeToDelete = ref(null);
const deleting = useForm({});
const { hasPermission } = usePermissions();

const reload = (preserveKey = false) => {
    router.get(
        route("room-types.index"),
        {
            search: search.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["roomTypes"],
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

onMounted(() => {
    if (props.filters.search) {
        search.value = props.filters.search;
    }
});

const confirmDelete = (roomType) => {
    roomTypeToDelete.value = roomType;
};

const destroyRoomType = () => {
    deleting.delete(route("room-types.destroy", roomTypeToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            roomTypeToDelete.value = null;
        },
    });
};

const formatPrice = (price) =>
    `₦${Number(price).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
</script>

<template>
    <Head title="Room Types" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-1 items-center justify-between gap-4">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-900"
                    >
                        Room Types
                    </h2>
                    <p class="text-sm text-gray-500">
                        Manage the room categories at
                        {{
                            activeBranch
                                ? `${activeBranch.name} (${activeBranch.code})`
                                : "this branch"
                        }}.
                    </p>
                </div>
                <Link
                    v-if="hasPermission('manage-room-types')"
                    :href="route('room-types.create')"
                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                >
                    <AppIcon name="plus" classes="[&>svg]:h-4 [&>svg]:w-4" />
                    Add Room Type
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
                    placeholder="Search room types..."
                    class="w-full pl-9"
                />
            </div>
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
                                Name
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Base Price
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Capacity
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Beds
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Rooms
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
                        <tr
                            v-for="roomType in roomTypes.data"
                            :key="roomType.id"
                        >
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"
                                    >
                                        <AppIcon
                                            name="layers"
                                            classes="[&>svg]:h-5 [&>svg]:w-5"
                                        />
                                    </span>
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ roomType.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ roomType.bed_configuration }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ formatPrice(roomType.base_price) }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ roomType.capacity }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ roomType.number_of_beds }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ roomType.rooms_count }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        roomType.is_active
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-gray-100 text-gray-500'
                                    "
                                >
                                    {{
                                        roomType.is_active
                                            ? "Active"
                                            : "Inactive"
                                    }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div
                                    v-if="hasPermission('manage-room-types')"
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'room-types.edit',
                                                roomType.id,
                                            )
                                        "
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
                                        @click="confirmDelete(roomType)"
                                    >
                                        <AppIcon
                                            name="x"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!roomTypes.data.length">
                            <td colspan="7" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">
                                    No room types found
                                </p>
                                <p class="mt-1 text-sm text-gray-400">
                                    {{
                                        search
                                            ? "Try adjusting your filters."
                                            : "Get started by adding your first room type."
                                    }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="roomTypes.total > roomTypes.per_page"
                class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3"
            >
                <p class="text-xs text-gray-500">
                    Showing
                    <span class="font-medium">{{ roomTypes.from }}</span>
                    to
                    <span class="font-medium">{{ roomTypes.to }}</span>
                    of
                    <span class="font-medium">{{ roomTypes.total }}</span>
                    room types
                </p>
                <nav class="flex items-center gap-1">
                    <template v-for="link in roomTypes.links" :key="link.label">
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

        <Modal :show="!!roomTypeToDelete" @close="roomTypeToDelete = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete {{ roomTypeToDelete?.name }}?
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    This will permanently remove this room type record. This
                    action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="roomTypeToDelete = null">
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        :disabled="deleting.processing"
                        @click="destroyRoomType"
                    >
                        {{
                            deleting.processing
                                ? "Deleting..."
                                : "Delete Room Type"
                        }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
