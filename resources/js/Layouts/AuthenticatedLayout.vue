<script setup>
import { computed, ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import SidebarNavDisabled from "@/Components/SidebarNavDisabled.vue";
import SidebarNavLink from "@/Components/SidebarNavLink.vue";
import FlashToast from "@/Components/FlashToast.vue";
import { usePermissions } from "@/Composables/usePermissions";

const showingNavigationSidebar = ref(false);

const navigation = [
    {
        label: "Main",
        items: [
            {
                label: "Dashboard",
                icon: "dashboard",
                href: "dashboard",
            },
        ],
    },
    {
        label: "Property",
        items: [
            {
                label: "Organizations",
                icon: "building",
                href: "organizations.index",
                active: "organizations.*",
            },
            {
                label: "Branches",
                icon: "hotel",
                href: "branches.index",
                active: "branches.*",
            },
            {
                label: "Room Types",
                icon: "layers",
                href: "room-types.index",
                active: "room-types.*",
            },
            {
                label: "Rooms",
                icon: "bed",
                href: "rooms.index",
                active: "rooms.*",
            },
        ],
    },
    {
        label: "Operations",
        items: [
            {
                label: "Reservations",
                icon: "calendar",
                href: "reservations.index",
                active: "reservations.*",
            },
            {
                label: "Guests",
                icon: "users",
                href: "guests.index",
                active: "guests.*",
            },
            {
                label: "Room Transfers",
                icon: "move",
                href: "room-transfers.index",
                active: "room-transfers.*",
            },
            {
                label: "Housekeeping",
                icon: "sparkles",
                href: "housekeeping.index",
                active: "housekeeping.*",
            },
            {
                label: "Maintenance",
                icon: "wrench",
                href: "maintenance.index",
                active: "maintenance.*",
            },
            {
                label: "Services",
                icon: "concierge-bell",
                href: "services.index",
                active: "services.*",
            },
            {
                label: "Restaurant",
                icon: "utensils",
                href: "restaurant.index",
                active: "restaurant.*",
            },
            {
                label: "Menu",
                icon: "inbox",
                href: "menu.index",
                active: "menu.*",
            },
            {
                label: "Laundry",
                icon: "shirt",
                href: "laundry.index",
                active: "laundry.*",
            },
            {
                label: "Check-ins",
                icon: "log-in",
                href: "checkins.index",
                active: "checkins.*",
            },
            {
                label: "Check-outs",
                icon: "log-out",
                href: "checkouts.index",
                active: "checkouts.*",
            },
            {
                label: "Expenses",
                icon: "receipt",
                href: "expenses.index",
                active: "expenses.*",
            },
            {
                label: "Staff",
                icon: "users",
                href: "staff.index",
                active: "staff.*",
            },
            {
                label: "Notifications",
                icon: "bell",
                href: "notifications.index",
                active: "notifications.*",
            },
            {
                label: "Audit Logs",
                icon: "clipboard-list",
                href: "audit-logs.index",
                active: "audit-logs.*",
            },
        ],
    },
    {
        label: "Finance",
        items: [
            {
                label: "Payments",
                icon: "card",
                href: "payments.index",
                active: "payments.*",
            },
            {
                label: "Invoices",
                icon: "receipt",
                href: "invoices.index",
                active: "invoices.*",
            },
        ],
    },
    {
        label: "Reports",
        items: [
            {
                label: "Reports & Analytics",
                icon: "chart",
                href: "reports.index",
                active: "reports.*",
            },
        ],
    },
    {
        label: "System",
        items: [
            {
                label: "Users",
                icon: "user",
                href: "users.index",
                active: "users.*",
            },
            {
                label: "Roles & Permissions",
                icon: "shield",
                href: "roles.index",
                active: "roles.*",
            },
            {
                label: "Settings",
                icon: "settings",
                href: "settings.index",
                active: "settings.*",
            },
        ],
    },
];

const isActive = (item) =>
    item.href ? route().current(item.active ?? item.href) : false;
const isDisabled = (item) => !item.href;

const navPermissions = {
    dashboard: "view-dashboard",
    "organizations.index": "view-hotels",
    "branches.index": "view-branches",
    "room-types.index": "view-room-types",
    "rooms.index": "view-rooms",
    "reservations.index": "view-reservations",
    "guests.index": "view-guests",
    "room-transfers.index": "manage-room-transfers",
    "housekeeping.index": "view-housekeeping",
    "maintenance.index": "view-maintenance",
    "services.index": "view-services",
    "restaurant.index": "manage-restaurant-orders",
    "menu.index": "manage-menu-items",
    "laundry.index": "view-laundry",
    "checkins.index": "manage-checkins",
    "checkouts.index": "manage-checkouts",
    "expenses.index": "view-expenses",
    "staff.index": "view-staff",
    "notifications.index": "view-notifications",
    "audit-logs.index": "view-audit-logs",
    "payments.index": "view-payments",
    "invoices.index": "view-invoices",
    "reports.index": "view-reports",
    "users.index": "view-users",
    "roles.index": "manage-roles",
    "settings.index": "manage-settings",
};

const { hasPermission } = usePermissions();

const visibleNavigation = computed(() =>
    navigation
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => {
                const required = navPermissions[item.href];
                return !required || hasPermission(required);
            }),
        }))
        .filter((group) => group.items.length > 0),
);

const switchBranch = (id) => {
    router.post(
        route("branches.switch"),
        { branch: id },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["branches", "activeBranch", "flash"],
        },
    );
};

const switchOrganization = (id) => {
    router.post(
        route("organizations.switch"),
        { organization: id },
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Mobile sidebar overlay -->
        <div
            v-if="showingNavigationSidebar"
            class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"
            @click="showingNavigationSidebar = false"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:translate-x-0',
                showingNavigationSidebar
                    ? 'translate-x-0'
                    : '-translate-x-full',
            ]"
        >
            <!-- Logo -->
            <div
                class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 px-5"
            >
                <Link href="/" class="flex items-center gap-2.5">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white"
                    >
                        <AppIcon
                            name="building"
                            classes="[&>svg]:h-5 [&>svg]:w-5"
                        />
                    </span>
                    <span
                        class="text-base font-bold tracking-tight text-gray-900"
                    >
                        Heritage Hotels
                    </span>
                </Link>

                <button
                    type="button"
                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden"
                    @click="showingNavigationSidebar = false"
                >
                    <AppIcon name="x" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                <div v-for="group in visibleNavigation" :key="group.label">
                    <p
                        class="mb-1.5 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400"
                    >
                        {{ group.label }}
                    </p>

                    <div class="space-y-1">
                        <template v-for="item in group.items" :key="item.label">
                            <SidebarNavLink
                                v-if="!isDisabled(item)"
                                :href="route(item.href)"
                                :active="isActive(item)"
                            >
                                <template #icon>
                                    <AppIcon :name="item.icon" />
                                </template>
                                {{ item.label }}
                            </SidebarNavLink>

                            <SidebarNavDisabled v-else>
                                <template #icon>
                                    <AppIcon :name="item.icon" />
                                </template>
                                {{ item.label }}
                            </SidebarNavDisabled>
                        </template>
                    </div>
                </div>
            </nav>

            <!-- Sidebar footer -->
            <div class="shrink-0 border-t border-gray-200 p-3">
                <div class="rounded-lg bg-gray-50 px-3 py-2.5">
                    <p
                        class="text-[11px] font-medium uppercase tracking-wide text-gray-400"
                    >
                        Signed in as
                    </p>
                    <p class="truncate text-sm font-semibold text-gray-900">
                        {{ $page.props.auth.user.name }}
                    </p>
                </div>
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex min-h-screen flex-col lg:pl-64">
            <!-- Topbar -->
            <header
                class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 sm:px-6"
            >
                <!-- Mobile hamburger -->
                <button
                    type="button"
                    class="rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden"
                    @click="showingNavigationSidebar = true"
                >
                    <AppIcon name="menu" />
                </button>

                <!-- Organization selector -->
                <div
                    v-if="$page.props.canFilterOrganizations"
                    class="hidden items-center md:flex"
                >
                    <Dropdown align="left" width="64">
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                <AppIcon
                                    name="building"
                                    classes="[&>svg]:h-4 [&>svg]:w-4 text-gray-400"
                                />
                                <span class="max-w-[10rem] truncate">
                                    {{
                                        $page.props.activeOrganizationId
                                            ? ($page.props.navOrganizations.find(
                                                  (org) =>
                                                      org.id ===
                                                      $page.props
                                                          .activeOrganizationId,
                                              )?.name ?? "All organizations")
                                            : "All organizations"
                                    }}
                                </span>
                                <AppIcon
                                    name="chevronDown"
                                    classes="[&>svg]:h-4 [&>svg]:w-4 text-gray-400"
                                />
                            </button>
                        </template>

                        <template #content>
                            <div
                                class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400"
                            >
                                Organization
                            </div>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 px-4 py-2 text-start text-sm text-gray-700 hover:bg-gray-100"
                                :class="{
                                    'bg-indigo-50 font-medium text-indigo-700':
                                        !$page.props.activeOrganizationId,
                                }"
                                @click="switchOrganization(null)"
                            >
                                <span class="truncate">
                                    All organizations
                                </span>
                                <span class="text-xs text-gray-400">
                                    Everything
                                </span>
                            </button>
                            <button
                                v-for="org in $page.props.navOrganizations"
                                :key="org.id"
                                type="button"
                                class="flex w-full items-center justify-between gap-3 px-4 py-2 text-start text-sm text-gray-700 hover:bg-gray-100"
                                :class="{
                                    'bg-indigo-50 font-medium text-indigo-700':
                                        $page.props.activeOrganizationId ===
                                        org.id,
                                }"
                                @click="switchOrganization(org.id)"
                            >
                                <span class="truncate">{{ org.name }}</span>
                                <span class="text-xs text-gray-400">
                                    {{ org.code }}
                                </span>
                            </button>
                        </template>
                    </Dropdown>
                </div>

                <!-- Branch selector -->
                <div class="flex items-center">
                    <Dropdown align="left" width="48">
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                :disabled="!$page.props.branches.length"
                                :class="{
                                    'cursor-not-allowed opacity-60 hover:bg-transparent':
                                        !$page.props.branches.length,
                                }"
                            >
                                <AppIcon
                                    name="building"
                                    classes="[&>svg]:h-4 [&>svg]:w-4 text-gray-400"
                                />
                                <span class="max-w-[10rem] truncate">
                                    {{
                                        $page.props.activeBranch
                                            ? $page.props.activeBranch.name
                                            : "No property"
                                    }}
                                </span>
                                <AppIcon
                                    name="chevronDown"
                                    classes="[&>svg]:h-4 [&>svg]:w-4 text-gray-400"
                                />
                            </button>
                        </template>

                        <template #content>
                            <div
                                class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400"
                            >
                                Branches
                            </div>
                            <div v-if="$page.props.branches.length">
                                <button
                                    v-for="branch in $page.props.branches"
                                    :key="branch.id"
                                    type="button"
                                    class="flex w-full items-center justify-between gap-3 px-4 py-2 text-start text-sm text-gray-700 hover:bg-gray-100"
                                    :class="{
                                        'bg-indigo-50 font-medium text-indigo-700':
                                            $page.props.activeBranch?.id ===
                                            branch.id,
                                    }"
                                    @click="switchBranch(branch.id)"
                                >
                                    <span class="truncate">
                                        {{ branch.name }}
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        {{ branch.code }}
                                    </span>
                                </button>
                            </div>
                            <p v-else class="px-4 py-3 text-sm text-gray-400">
                                No branches yet — add one from the Branches
                                menu.
                            </p>
                        </template>
                    </Dropdown>
                </div>

                <div class="flex-1"></div>

                <!-- Notifications -->
                <button
                    type="button"
                    class="relative rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    title="Notifications"
                >
                    <AppIcon name="bell" />
                    <span
                        class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-indigo-600"
                    ></span>
                </button>

                <!-- User dropdown -->
                <div class="relative">
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg p-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                            >
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white"
                                >
                                    {{
                                        $page.props.auth.user.name
                                            .split(" ")
                                            .map((part) => part.charAt(0))
                                            .join("")
                                            .slice(0, 2)
                                            .toUpperCase()
                                    }}
                                </span>
                                <span class="hidden sm:block">
                                    {{ $page.props.auth.user.name }}
                                </span>
                                <AppIcon
                                    name="chevronDown"
                                    classes="[&>svg]:h-4 [&>svg]:w-4 text-gray-400"
                                />
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.edit')">
                                Profile
                            </DropdownLink>
                            <DropdownLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Page header -->
            <header
                v-if="$slots.header"
                class="border-b border-gray-200 bg-white"
            >
                <div class="flex items-center gap-4 px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page content -->
            <FlashToast />
            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
