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
import { formatMoney as formatPrice } from "@/Utils/format.js";

const props = defineProps({
    menuItems: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
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

const search = ref(props.filters?.search ?? "");
const category = ref(props.filters?.category ?? "");
const status = ref(props.filters?.status ?? "");
const itemToDelete = ref(null);
const deleting = useForm({});
const { hasPermission } = usePermissions();

const reload = () => {
    router.get(
        route("menu.index"),
        {
            search: search.value || undefined,
            category: category.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, only: ["menuItems"] },
    );
};

let searchTimer;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => reload(), 300);
});

watch(category, () => reload());
watch(status, () => reload());

const confirmDelete = (item) => {
    itemToDelete.value = item;
};

const destroyItem = () => {
    deleting.delete(route("menu.destroy", itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            itemToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Restaurant Menu" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-1 items-center justify-between gap-4">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-900"
                    >
                        Restaurant Menu
                    </h2>
                    <p class="text-sm text-gray-500">
                        Available food and drink items
                        <template v-if="activeBranch">
                            · {{ activeBranch.name }}
                        </template>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <Link
                        v-if="hasPermission('manage-restaurant-orders')"
                        :href="route('restaurant.create')"
                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        <AppIcon
                            name="receipt"
                            classes="[&>svg]:h-4 [&>svg]:w-4"
                        />
                        New Order
                    </Link>
                    <Link
                        v-if="hasPermission('manage-menu-items')"
                        :href="route('menu.create')"
                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                    >
                        <AppIcon
                            name="plus"
                            classes="[&>svg]:h-4 [&>svg]:w-4"
                        />
                        Add Item
                    </Link>
                </div>
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
                    placeholder="Search items..."
                    class="w-full pl-9"
                />
            </div>
            <select
                v-model="category"
                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-44"
            >
                <option value="">All categories</option>
                <option
                    v-for="option in categories"
                    :key="option"
                    :value="option"
                >
                    {{ option }}
                </option>
            </select>
            <select
                v-model="status"
                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-44"
            >
                <option value="">All statuses</option>
                <option value="available">Available</option>
                <option value="unavailable">Unavailable</option>
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
                                Item
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Category
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Price
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
                        <tr v-for="item in menuItems.data" :key="item.id">
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ item.name }}
                                </p>
                                <p
                                    v-if="item.description"
                                    class="mt-0.5 max-w-md truncate text-xs text-gray-500"
                                >
                                    {{ item.description }}
                                </p>
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600"
                            >
                                {{ item.category }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900"
                            >
                                {{ formatPrice(item.price) }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        item.is_available
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-gray-100 text-gray-500'
                                    "
                                >
                                    {{
                                        item.is_available
                                            ? "Available"
                                            : "Unavailable"
                                    }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div
                                    v-if="hasPermission('manage-menu-items')"
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="route('menu.edit', item.id)"
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
                                        @click="confirmDelete(item)"
                                    >
                                        <AppIcon
                                            name="x"
                                            classes="[&>svg]:h-4 [&>svg]:w-4"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!menuItems.data.length">
                            <td colspan="5" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">
                                    No menu items found
                                </p>
                                <p class="mt-1 text-sm text-gray-400">
                                    {{
                                        search || category || status
                                            ? "Try adjusting your filters."
                                            : "Get started by adding your first menu item."
                                    }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="menuItems.total > menuItems.per_page"
                class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3"
            >
                <p class="text-xs text-gray-500">
                    Showing
                    <span class="font-medium">{{ menuItems.from }}</span>
                    to <span class="font-medium">{{ menuItems.to }}</span> of
                    <span class="font-medium">{{ menuItems.total }}</span>
                    items
                </p>
                <nav class="flex items-center gap-1">
                    <template v-for="link in menuItems.links" :key="link.label">
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

        <Modal :show="!!itemToDelete" @close="itemToDelete = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete {{ itemToDelete?.name }}?
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    This will remove the item from the restaurant menu. This
                    action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="itemToDelete = null">
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        :disabled="deleting.processing"
                        @click="destroyItem"
                    >
                        {{
                            deleting.processing ? "Deleting..." : "Delete Item"
                        }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
