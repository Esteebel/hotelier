<script setup>
import { onMounted, ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { usePermissions } from "@/Composables/usePermissions";

const props = defineProps({
    guests: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    activeBranch: { type: Object, default: null },
});

const search = ref(props.filters.search ?? "");
const guestToDelete = ref(null);
const deleting = useForm({});
const { hasPermission } = usePermissions();

const reload = (preserveKey = false) => {
    router.get(
        route("guests.index"),
        {
            search: search.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["guests"],
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

const confirmDelete = (guest) => {
    guestToDelete.value = guest;
};

const destroyGuest = () => {
    deleting.delete(route("guests.destroy", guestToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            guestToDelete.value = null;
        },
    });
};

const fullName = (guest) =>
    `${guest.first_name ?? ""} ${guest.last_name ?? ""}`.trim();
</script>

<template>
    <Head title="Guests" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-1 items-center justify-between gap-4">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-900"
                    >
                        Guests
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{
                            activeBranch
                                ? `${activeBranch.name} (${activeBranch.code})`
                                : "All branches"
                        }}
                    </p>
                </div>
                <Link
                    v-if="hasPermission('manage-guests')"
                    :href="route('guests.create')"
                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                >
                    <AppIcon name="plus" classes="[&>svg]:h-4 [&>svg]:w-4" />
                    Add Guest
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
                    placeholder="Search guests..."
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
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Guest
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Contact
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Nationality
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Status
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                VIP
                            </th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="guest in guests.data" :key="guest.id">
                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ fullName(guest) }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{
                                        guest.id_number
                                            ? `${guest.id_type ?? "ID"} ${guest.id_number}`
                                            : ""
                                    }}
                                </p>
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                <p>{{ guest.phone ?? "—" }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ guest.email ?? "—" }}
                                </p>
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ guest.nationality ?? "—" }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="statusBadge(guest)"
                                >
                                    {{ statusLabel(guest) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm">
                                <span
                                    v-if="guest.is_vip"
                                    class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800"
                                    >VIP</span
                                >
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div
                                    v-if="hasPermission('manage-guests')"
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="route('guests.edit', guest.id)"
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
                                        @click="confirmDelete(guest)"
                                    >
                                        <AppIcon
                                            name="x"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!guests.data.length">
                            <td colspan="6" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">
                                    No guests found
                                </p>
                                <p class="mt-1 text-sm text-gray-400">
                                    {{
                                        search
                                            ? "Try adjusting your search."
                                            : "Get started by adding your first guest."
                                    }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="guests.total > guests.per_page"
                class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3"
            >
                <p class="text-xs text-gray-500">
                    Showing
                    <span class="font-medium">{{ guests.from }}</span> to
                    <span class="font-medium">{{ guests.to }}</span> of
                    <span class="font-medium">{{ guests.total }}</span> guests
                </p>
                <nav class="flex items-center gap-1">
                    <template v-for="link in guests.links" :key="link.label">
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

        <Modal :show="!!guestToDelete" @close="guestToDelete = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete {{ fullName(guestToDelete) }}?
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    This will permanently remove this guest record. This action
                    cannot be undone.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="guestToDelete = null"
                        >Cancel</SecondaryButton
                    >
                    <DangerButton
                        :disabled="deleting.processing"
                        @click="destroyGuest"
                    >
                        {{
                            deleting.processing ? "Deleting..." : "Delete Guest"
                        }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script>
export default {
    methods: {
        statusBadge(guest) {
            return (
                {
                    active: "bg-emerald-50 text-emerald-700 ring-emerald-600/20",
                    former: "bg-gray-50 text-gray-700 ring-gray-600/20",
                    blacklisted: "bg-red-50 text-red-700 ring-red-600/20",
                    archived: "bg-amber-50 text-amber-700 ring-amber-600/20",
                }[guest.status] ?? "bg-gray-50 text-gray-700 ring-gray-600/20"
            );
        },
        statusLabel(guest) {
            return (
                {
                    active: "Active",
                    former: "Former",
                    blacklisted: "Blacklisted",
                    archived: "Archived",
                }[guest.status] ?? guest.status
            );
        },
    },
};
</script>
