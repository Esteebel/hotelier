<script setup>
import AppIcon from "@/Components/AppIcon.vue";
import { Head } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
});

const variant = ref("classic");
const brandName = "Heritage Hotels";
</script>

<template>
    <Head :title="`${brandName} — Hotel Management`" />

    <!-- ===== CLASSIC ===== -->
    <section
        v-if="variant === 'classic'"
        class="relative min-h-screen overflow-hidden bg-[#f5f0e3] text-[#2a2418]"
    >
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.5]"
            style="
                background-image: radial-gradient(
                    circle at 1px 1px,
                    #c9bb8f 1px,
                    transparent 0
                );
                background-size: 22px 22px;
            "
        ></div>

        <nav
            class="relative z-10 flex items-center justify-between px-8 py-6 lg:px-16"
        >
            <div class="flex items-center gap-2.5">
                <AppIcon
                    name="building"
                    classes="[&>svg]:size-6 text-[#8f7a3c]"
                />
                <span class="text-lg font-semibold tracking-tight"
                    >Heritage Hotels</span
                >
            </div>
            <div class="flex items-center gap-2 text-sm">
                <Link
                    :href="route('dashboard')"
                    class="rounded-md px-4 py-2 text-[#4a4235] hover:text-black"
                >
                    Dashboard
                </Link>
                <Link
                    :href="route('login')"
                    class="rounded-md px-4 py-2 text-[#4a4235] hover:text-black"
                >
                    Sign in
                </Link>
                <button
                    class="rounded-md border border-[#c9bb8f] px-4 py-2 text-[#4a4235] hover:bg-[#e9dfc4]"
                    @click="variant = 'modern'"
                >
                    Switch to Modern
                </button>
            </div>
        </nav>

        <main
            class="relative z-10 mx-auto grid max-w-6xl items-center gap-12 px-8 py-16 lg:grid-cols-2 lg:px-16"
        >
            <div>
                <p
                    class="text-sm font-medium uppercase tracking-[0.2em] text-[#8f7a3c]"
                >
                    Welcome to
                </p>
                <h1 class="mt-4 text-4xl font-bold leading-tight lg:text-5xl">
                    Heritage Hotels
                </h1>
                <p class="mt-6 max-w-md text-lg leading-relaxed text-[#70685a]">
                    A complete hotel-management platform — organizations,
                    branches, rooms, and the people who run them.
                </p>
                <a
                    href="/dashboard"
                    class="mt-10 inline-flex items-center gap-2 rounded-lg bg-[#8f7a3c] px-6 py-3 text-sm font-semibold text-white shadow-md hover:bg-[#7a6833]"
                >
                    Explore the platform
                </a>
            </div>
            <div class="hidden lg:block">
                <div
                    class="rounded-2xl border border-[#e2d8be] bg-white/80 p-8 shadow-xl"
                >
                    <div
                        class="flex items-center justify-between border-b border-[#ece4d0] pb-4"
                    >
                        <p class="text-sm font-semibold text-[#4a4235]">
                            The Heritage Collection
                        </p>
                    </div>
                    <div class="space-y-4 pt-6 text-sm text-[#70685a]">
                        <div
                            class="flex items-center justify-between border-b border-[#ece4d0] pb-3"
                        >
                            <span>Organizations</span>
                            <span class="font-medium text-[#8f7a3c]">24</span>
                        </div>
                        <div
                            class="flex items-center justify-between border-b border-[#ece4d0] pb-3"
                        >
                            <span>Active Branches</span>
                            <span class="font-medium text-[#8f7a3c]">68</span>
                        </div>
                        <div
                            class="flex items-center justify-between border-b border-[#ece4d0] pb-3"
                        >
                            <span>Rooms</span>
                            <span class="font-medium text-[#8f7a3c]"
                                >1,254</span
                            >
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Happy Guests</span>
                            <span class="font-medium text-[#8f7a3c]">40k+</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </section>

    <!-- ===== MODERN ===== -->
    <section
        v-else
        class="relative min-h-screen overflow-hidden bg-[#070b14] text-white"
    >
        <div
            class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-indigo-500/25 blur-[120px]"
        ></div>
        <div
            class="absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-emerald-500/20 blur-[100px]"
        ></div>

        <nav
            class="relative z-10 flex items-center justify-between px-8 py-6 lg:px-16"
        >
            <div class="flex items-center gap-2.5">
                <AppIcon
                    name="sparkles"
                    classes="[&>svg]:size-5 text-indigo-400"
                />
                <span class="text-lg font-semibold tracking-tight"
                    >heritage.io</span
                >
            </div>
            <div class="flex items-center gap-2 text-sm">
                <Link
                    :href="route('dashboard')"
                    class="rounded-md px-4 py-2 text-white/70 hover:text-white"
                >
                    Dashboard
                </Link>
                <Link
                    :href="route('login')"
                    class="rounded-md px-4 py-2 text-white/70 hover:text-white"
                >
                    Sign in
                </Link>
                <button
                    class="rounded-md border border-white/15 px-4 py-2 text-white/80 hover:bg-white/5"
                    @click="variant = 'classic'"
                >
                    Switch to Classic
                </button>
            </div>
        </nav>

        <main
            class="relative z-10 mx-auto grid max-w-6xl items-center gap-12 px-8 py-16 lg:grid-cols-2 lg:px-16"
        >
            <div>
                <p
                    class="text-sm font-medium uppercase tracking-[0.2em] text-indigo-400"
                >
                    Welcome to
                </p>
                <h1
                    class="mt-4 bg-gradient-to-r from-indigo-300 to-fuchsia-300 bg-clip-text text-4xl font-extrabold text-transparent lg:text-5xl"
                >
                    Heritage Hotels
                </h1>
                <p class="mt-6 max-w-md text-lg leading-relaxed text-gray-300">
                    Every stay, one platform. Organizations, branches, rooms and
                    access — at scale.
                </p>
                <a
                    href="/dashboard"
                    class="mt-10 inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 hover:bg-indigo-400"
                >
                    Explore the platform
                    <AppIcon name="arrowRight" classes="[&>svg]:size-4" />
                </a>
            </div>
            <div class="hidden lg:block">
                <div
                    class="rounded-2xl border border-white/10 bg-white/5 p-8 backdrop-blur"
                >
                    <div
                        class="flex items-center justify-between border-b border-white/10 pb-4"
                    >
                        <p class="text-sm font-semibold text-white">
                            heritage.io — by the numbers
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-6">
                        <div class="rounded-xl bg-white/5 p-4">
                            <AppIcon
                                name="building"
                                classes="[&>svg]:size-4 text-indigo-400"
                            />
                            <p class="mt-3 text-2xl font-bold text-white">24</p>
                            <p class="text-xs text-gray-400">Organizations</p>
                        </div>
                        <div class="rounded-xl bg-white/5 p-4">
                            <AppIcon
                                name="shield"
                                classes="[&>svg]:size-4 text-emerald-400"
                            />
                            <p class="mt-3 text-2xl font-bold text-white">68</p>
                            <p class="text-xs text-gray-400">Active Branches</p>
                        </div>
                        <div class="rounded-xl bg-white/5 p-4">
                            <AppIcon
                                name="layers"
                                classes="[&>svg]:size-4 text-fuchsia-400"
                            />
                            <p class="mt-3 text-2xl font-bold text-white">
                                1,254
                            </p>
                            <p class="text-xs text-gray-400">Rooms</p>
                        </div>
                        <div class="rounded-xl bg-white/5 p-4">
                            <AppIcon
                                name="sparkles"
                                classes="[&>svg]:size-4 text-amber-400"
                            />
                            <p class="mt-3 text-2xl font-bold text-white">
                                40k+
                            </p>
                            <p class="text-xs text-gray-400">Happy Guests</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </section>
</template>
