<script setup>
import { onMounted, ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import TextInput from "@/Components/TextInput.vue";
import { usePermissions } from "@/Composables/usePermissions";

const props = defineProps({
    branches: {
        type: Object,
        required: true,
    },
    organizations: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search ?? "");
const status = ref(props.filters.status ?? "");
const organizationId = ref(props.filters.organization_id ?? "");
const branchToDelete = ref(null);
const deleting = useForm({});
const { hasPermission } = usePermissions();

const reload = (preserveKey = false) => {
    router.get(
        route("branches.index"),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            organization_id: organizationId.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["branches"],
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
watch(organizationId, () => reload());

onMounted(() => {
    if (props.filters.search) {
        search.value = props.filters.search;
    }
});

const confirmDelete = (branch) => {
    branchToDelete.value = branch;
};

const destroyBranch = () => {
    deleting.delete(route("branches.destroy", branchToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            branchToDelete.value = null;
        },
    });
};

const starLabel = (count) => (count === 1 ? "1 star" : `${count} stars`);
</script>

<template>
    <Head title="Branches" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-1 items-center justify-between gap-4">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-900"
                    >
                        Branches
                    </h2>
                    <p class="text-sm text-gray-500">
                        Manage your branch properties and rooms.
                    </p>
                </div>
                <Link
                    v-if="hasPermission('manage-branches')"
                    :href="route('branches.create')"
                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                >
                    <AppIcon name="plus" classes="[&>svg]:h-4 [&>svg]:w-4" />
                    Add Branch
                </Link>
            </div>
        </template>

        <!-- Flash message -->
        <div
            v-if="$page.props.flash?.success"
            class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
            {{ $page.props.flash.success }}
        </div>

        <!-- Filters -->
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
                    placeholder="Search branches..."
                    class="w-full pl-9"
                />
            </div>
            <SelectInput v-model="organizationId" class="sm:w-52">
                <option value="">All organizations</option>
                <option
                    v-for="org in organizations"
                    :key="org.id"
                    :value="org.id"
                >
                    {{ org.name }}
                </option>
            </SelectInput>
            <SelectInput v-model="status" class="sm:w-44">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </SelectInput>
        </div>

        <!-- Table -->
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
                                Branch
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
                        <tr v-for="branch in branches.data" :key="branch.id">
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
                                            {{ branch.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ branch.code }}
                                            <template
                                                v-if="branch.organization"
                                            >
                                                · {{ branch.organization.name }}
                                            </template>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ branch.city }}, {{ branch.state }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ starLabel(branch.star_rating) }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ branch.rooms_count }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        branch.status === 'active'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-gray-100 text-gray-500'
                                    "
                                >
                                    {{ branch.status_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        v-if="hasPermission('manage-branches')"
                                        :href="
                                            route('branches.edit', branch.id)
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
                                        v-if="hasPermission('manage-branches')"
                                        type="button"
                                        class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600"
                                        title="Delete"
                                        @click="confirmDelete(branch)"
                                    >
                                        <AppIcon
                                            name="x"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!branches.data.length">
                            <td colspan="6" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">
                                    No branches found
                                </p>
                                <p class="mt-1 text-sm text-gray-400">
                                    {{
                                        search || status || organizationId
                                            ? "Try adjusting your filters."
                                            : "Get started by adding your first branch."
                                    }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="branches.total > branches.per_page"
                class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3"
            >
                <p class="text-xs text-gray-500">
                    Showing
                    <span class="font-medium">{{ branches.from }}</span>
                    to
                    <span class="font-medium">{{ branches.to }}</span>
                    of
                    <span class="font-medium">{{ branches.total }}</span>
                    branches
                </p>
                <nav class="flex items-center gap-1">
                    <template v-for="link in branches.links" :key="link.label">
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

        <!-- Delete confirmation -->
        <Modal :show="!!branchToDelete" @close="branchToDelete = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete {{ branchToDelete?.name }}?
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    This will permanently remove this branch record. This action
                    cannot be undone.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="branchToDelete = null">
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        :disabled="deleting.processing"
                        @click="destroyBranch"
                    >
                        {{
                            deleting.processing
                                ? "Deleting..."
                                : "Delete Branch"
                        }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
