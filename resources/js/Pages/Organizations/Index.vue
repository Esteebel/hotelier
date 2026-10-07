<script setup>
import { ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { usePermissions } from "@/Composables/usePermissions";

const props = defineProps({
    organizations: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "");
const orgToDelete = ref(null);
const deleting = useForm({});
const { hasPermission, isSuperAdmin } = usePermissions();

const reload = () => {
    router.get(
        route("organizations.index"),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, only: ["organizations"] },
    );
};

let searchTimer;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => reload(), 300);
});

watch(status, () => reload());

const confirmDelete = (organization) => {
    orgToDelete.value = organization;
};

const destroyOrganization = () => {
    deleting.delete(route("organizations.destroy", orgToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            orgToDelete.value = null;
        },
    });
};

const starLabel = (count) => (count === 1 ? "1 star" : `${count} stars`);
</script>

<template>
    <Head title="Organizations" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-1 items-center justify-between gap-4">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-900"
                    >
                        Organizations
                    </h2>
                    <p class="text-sm text-gray-500">
                        Parent hotel companies that own your branches.
                    </p>
                </div>
                <Link
                    v-if="isSuperAdmin()"
                    :href="route('organizations.create')"
                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                >
                    <AppIcon name="plus" classes="[&>svg]:h-4 [&>svg]:w-4" />
                    Add Organization
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
                    placeholder="Search name, code or city..."
                    class="w-full pl-9"
                />
            </div>
            <select
                v-model="status"
                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-44"
            >
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
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
                                Organization
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Location
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Rating
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Branches
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
                            v-for="organization in organizations.data"
                            :key="organization.id"
                        >
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"
                                    >
                                        <AppIcon
                                            name="building"
                                            classes="[&>svg]:h-5 [&>svg]:w-5"
                                        />
                                    </span>
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ organization.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ organization.code }}
                                            <template v-if="organization.city">
                                                · {{ organization.city }}
                                            </template>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ organization.city }},
                                {{ organization.state }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ starLabel(organization.star_rating) }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ organization.branches_count }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        organization.status === 'active'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-gray-100 text-gray-500'
                                    "
                                >
                                    {{ organization.status_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        v-if="hasPermission('manage-hotels')"
                                        :href="
                                            route(
                                                'organizations.edit',
                                                organization.id,
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
                                        v-if="isSuperAdmin()"
                                        type="button"
                                        class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600"
                                        title="Delete"
                                        @click="confirmDelete(organization)"
                                    >
                                        <AppIcon
                                            name="x"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!organizations.data.length">
                            <td colspan="6" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">
                                    No organizations found
                                </p>
                                <p class="mt-1 text-sm text-gray-400">
                                    {{
                                        search || status
                                            ? "Try adjusting your filters."
                                            : "Get started by adding your first organization."
                                    }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="organizations.total > organizations.per_page"
                class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3"
            >
                <p class="text-xs text-gray-500">
                    Showing
                    <span class="font-medium">{{ organizations.from }}</span>
                    to <span class="font-medium">{{ organizations.to }}</span>
                    of
                    <span class="font-medium">{{ organizations.total }}</span>
                    organizations
                </p>
                <nav class="flex items-center gap-1">
                    <template
                        v-for="link in organizations.links"
                        :key="link.label"
                    >
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

        <Modal :show="!!orgToDelete" @close="orgToDelete = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete {{ orgToDelete?.name }}?
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    This will soft-delete the organization record. This action
                    cannot be undone.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="orgToDelete = null">
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        :disabled="deleting.processing"
                        @click="destroyOrganization"
                    >
                        {{
                            deleting.processing
                                ? "Deleting..."
                                : "Delete Organization"
                        }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
