<script setup>
import { computed, ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import Dropdown from "@/Components/Dropdown.vue";
import FlashToast from "@/Components/FlashToast.vue";
import SidebarNavLink from "@/Components/SidebarNavLink.vue";

const page = usePage();
const showingNavigationSidebar = ref(false);

const navigation = [
    {
        label: "Main",
        items: [
            { label: "Home", icon: "dashboard", href: "portal.home" },
            { label: "Available Rooms", icon: "bed", href: "portal.rooms" },
            {
                label: "Reservations",
                icon: "calendar",
                href: "portal.reservations",
            },
        ],
    },
    {
        label: "Services",
        items: [
            { label: "Menu", icon: "inbox", href: "portal.menu" },
            { label: "My Orders", icon: "receipt", href: "portal.orders" },
            { label: "Bills", icon: "card", href: "portal.bills" },
        ],
    },
    {
        label: "Account",
        items: [
            { label: "Profile", icon: "userRound", href: "portal.profile" },
        ],
    },
];

const welcomeName = computed(() => {
    const name = page.props.auth?.user?.name ?? "";

    return name.split(" ")[0] || "Guest";
});

const notifications = computed(() => page.props.portalNotifications ?? []);

const unreadCount = computed(() =>
    Number(page.props.portalNotificationsUnread ?? 0),
);

const isActive = (href) => {
    const url = route(href, {}, false);

    return page.url === url || page.url.startsWith(`${url}/`);
};

const closeSidebar = () => {
    showingNavigationSidebar.value = false;
};

const logout = () => {
    router.post(route("logout"));
};

const markRead = (notificationId) => {
    router.post(
        route("portal.notifications.read", notificationId),
        {},
        { preserveScroll: true },
    );
};

const markAllRead = () => {
    router.post(
        route("portal.notifications.readAll"),
        {},
        { preserveScroll: true },
    );
};

const formatTime = (value) => {
    if (!value) {
        return "";
    }

    const date = new Date(String(value).replace(" ", "T"));

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString([], {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Persistent sidebar: always visible on lg+, toggled on small screens -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:translate-x-0"
            :class="
                showingNavigationSidebar ? 'translate-x-0' : '-translate-x-full'
            "
        >
            <!-- Brand -->
            <div
                class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 px-5"
            >
                <Link
                    :href="route('portal.home')"
                    class="flex items-center gap-2.5"
                    @click="closeSidebar"
                >
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white"
                    >
                        <AppIcon
                            name="hotel"
                            classes="[&>svg]:h-5 [&>svg]:w-5"
                        />
                    </span>
                    <span
                        class="text-base font-bold tracking-tight text-gray-900"
                    >
                        Guest Portal
                    </span>
                </Link>

                <button
                    type="button"
                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden"
                    aria-label="Close menu"
                    @click="closeSidebar"
                >
                    <AppIcon name="x" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                <div v-for="group in navigation" :key="group.label">
                    <p
                        class="mb-1.5 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400"
                    >
                        {{ group.label }}
                    </p>

                    <div class="space-y-1">
                        <SidebarNavLink
                            v-for="item in group.items"
                            :key="item.href"
                            :href="route(item.href)"
                            :active="isActive(item.href)"
                            @click="closeSidebar"
                        >
                            <template #icon>
                                <AppIcon :name="item.icon" />
                            </template>
                            {{ item.label }}
                        </SidebarNavLink>
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

                <button
                    type="button"
                    class="mt-2 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                    @click="logout"
                >
                    <AppIcon
                        name="logout"
                        classes="[&>svg]:h-5 [&>svg]:w-5 text-gray-400"
                    />
                    Log Out
                </button>
            </div>
        </aside>

        <!-- Mobile backdrop -->
        <div
            v-show="showingNavigationSidebar"
            class="fixed inset-0 z-30 bg-black/40 lg:hidden"
            aria-hidden="true"
            @click="closeSidebar"
        ></div>

        <!-- Main column -->
        <div class="flex min-h-screen flex-col lg:pl-64">
            <!-- Top bar -->
            <header
                class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 sm:px-6"
            >
                <button
                    type="button"
                    class="rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:hidden"
                    aria-label="Open menu"
                    @click="showingNavigationSidebar = true"
                >
                    <AppIcon name="menu" />
                </button>

                <Link
                    :href="route('portal.home')"
                    class="flex items-center gap-2"
                >
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white"
                    >
                        <AppIcon
                            name="hotel"
                            classes="[&>svg]:h-5 [&>svg]:w-5"
                        />
                    </span>
                    <span class="text-lg font-semibold text-gray-900">
                        Guest Portal
                    </span>
                </Link>

                <div class="flex-1"></div>

                <p
                    class="hidden text-sm font-medium text-gray-500 sm:block sm:text-base"
                >
                    Welcome,
                    <span class="font-semibold text-gray-900">{{
                        welcomeName
                    }}</span>
                </p>

                <!-- Notification bell -->
                <Dropdown
                    align="right"
                    width="96"
                    contentClasses="py-0 bg-white"
                >
                    <template #trigger>
                        <button
                            type="button"
                            class="relative rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                            title="Notifications"
                        >
                            <AppIcon name="bell" />
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1 text-[10px] font-bold text-white"
                            >
                                {{ unreadCount > 9 ? "9+" : unreadCount }}
                            </span>
                        </button>
                    </template>

                    <template #content>
                        <div
                            class="flex items-center justify-between border-b border-gray-100 px-4 py-3"
                        >
                            <p class="text-sm font-semibold text-gray-900">
                                Notifications
                            </p>
                            <span
                                v-if="unreadCount > 0"
                                class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700"
                            >
                                {{ unreadCount }} new
                            </span>
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            <button
                                v-for="notification in notifications"
                                :key="notification.id"
                                type="button"
                                class="flex w-full gap-3 px-4 py-3 text-start transition hover:bg-gray-50"
                                :class="
                                    notification.status === 'unread'
                                        ? 'bg-indigo-50/60'
                                        : ''
                                "
                                @click="markRead(notification.id)"
                            >
                                <span
                                    class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                    :class="
                                        notification.status === 'unread'
                                            ? 'bg-indigo-600'
                                            : 'bg-gray-300'
                                    "
                                ></span>
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <span
                                            class="truncate text-sm"
                                            :class="
                                                notification.status === 'unread'
                                                    ? 'font-semibold text-gray-900'
                                                    : 'font-medium text-gray-700'
                                            "
                                        >
                                            {{ notification.title }}
                                        </span>
                                        <span
                                            class="shrink-0 text-[11px] text-gray-400"
                                        >
                                            {{
                                                formatTime(
                                                    notification.created_at,
                                                )
                                            }}
                                        </span>
                                    </span>
                                    <span
                                        v-if="notification.message"
                                        class="mt-0.5 block text-xs text-gray-500"
                                    >
                                        {{ notification.message }}
                                    </span>
                                </span>
                            </button>

                            <p
                                v-if="!notifications.length"
                                class="px-4 py-6 text-center text-sm text-gray-500"
                            >
                                You're all caught up.
                            </p>
                        </div>

                        <div
                            v-if="unreadCount > 0"
                            class="border-t border-gray-100 px-3 py-2"
                        >
                            <button
                                type="button"
                                class="w-full rounded-md px-3 py-1.5 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-50"
                                @click="markAllRead"
                            >
                                Mark all as read
                            </button>
                        </div>
                    </template>
                </Dropdown>
            </header>

            <!-- Page content -->
            <FlashToast />
            <main class="flex-1 py-8">
                <slot />
            </main>
        </div>
    </div>
</template>
