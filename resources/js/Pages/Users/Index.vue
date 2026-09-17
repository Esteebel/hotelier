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
    users: {
        type: Object,
        required: true,
    },
    roles: {
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
const role = ref(props.filters.role ?? "");
const userToDelete = ref(null);
const deleting = useForm({});

const reload = (preserveKey = false) => {
    router.get(
        route("users.index"),
        {
            search: search.value || undefined,
            role: role.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["users"],
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
watch(role, () => reload());

onMounted(() => {
    if (props.filters.search) {
        search.value = props.filters.search;
    }
});

const isSuperAdmin = (user) => user.roles.includes("super-admin");

const confirmDelete = (user) => {
    userToDelete.value = user;
};

const destroyUser = () => {
    deleting.delete(route("users.destroy", userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            userToDelete.value = null;
        },
    });
};

const formattedDate = (value) =>
    value ? new Date(value).toLocaleDateString() : "—";
</script>

<template>
    <Head title="Users" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-1 items-center justify-between gap-4">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-900"
                    >
                        Users
                    </h2>
                    <p class="text-sm text-gray-500">
                        Manage system users and their access.
                    </p>
                </div>
                <Link
                    :href="route('users.create')"
                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                >
                    <AppIcon name="plus" classes="[&>svg]:h-4 [&>svg]:w-4" />
                    Add User
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
                    placeholder="Search users..."
                    class="w-full pl-9"
                />
            </div>
            <SelectInput v-model="role" class="sm:w-44">
                <option value="">All roles</option>
                <option
                    v-for="option in roles"
                    :key="option.name"
                    :value="option.name"
                >
                    {{ option.label }}
                </option>
            </SelectInput>
            <SelectInput v-model="status" class="sm:w-44">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
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
                                User
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Roles
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
                                Created
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
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"
                                    >
                                        <AppIcon
                                            name="user"
                                            classes="[&>svg]:h-5 [&>svg]:w-5"
                                        />
                                    </span>
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ user.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ user.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="userRole in user.roles"
                                        :key="userRole"
                                        class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"
                                    >
                                        <template
                                            v-if="userRole === 'super-admin'"
                                        >
                                            <AppIcon
                                                name="shield"
                                                classes="[&>svg]:h-3 [&>svg]:w-3 mr-1 text-amber-500"
                                            />
                                        </template>
                                        {{ userRole }}
                                    </span>
                                    <span
                                        v-if="!user.roles.length"
                                        class="text-xs text-gray-400"
                                    >
                                        No roles
                                    </span>
                                </div>
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ user.branches_count }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ formattedDate(user.created_at) }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        user.status === 'active'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-gray-100 text-gray-500'
                                    "
                                >
                                    {{ user.status_label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div
                                    v-if="isSuperAdmin(user)"
                                    class="flex items-center justify-end"
                                >
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700"
                                    >
                                        <AppIcon
                                            name="shield"
                                            classes="[&>svg]:h-3.5 [&>svg]:w-3.5"
                                        />
                                        Protected
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="route('users.edit', user.id)"
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
                                        @click="confirmDelete(user)"
                                    >
                                        <AppIcon
                                            name="trash"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!users.data.length">
                            <td colspan="6" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">
                                    No users found
                                </p>
                                <p class="mt-1 text-sm text-gray-400">
                                    {{
                                        search || role || status
                                            ? "Try adjusting your filters."
                                            : "Get started by adding your first user."
                                    }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="users.total > users.per_page"
                class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3"
            >
                <p class="text-xs text-gray-500">
                    Showing
                    <span class="font-medium">{{ users.from }}</span>
                    to
                    <span class="font-medium">{{ users.to }}</span>
                    of
                    <span class="font-medium">{{ users.total }}</span>
                    users
                </p>
                <nav class="flex items-center gap-1">
                    <template v-for="link in users.links" :key="link.label">
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

        <Modal :show="!!userToDelete" @close="userToDelete = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete {{ userToDelete?.name }}?
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    This will permanently remove this user's account and revoke
                    their access. This action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="userToDelete = null">
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        :disabled="deleting.processing"
                        @click="destroyUser"
                    >
                        {{
                            deleting.processing ? "Deleting..." : "Delete User"
                        }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
