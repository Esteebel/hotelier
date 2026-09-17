<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { ref } from 'vue';

const props = defineProps({
    showToggle: {
        type: Boolean,
        default: true,
    },
});

const variant = ref('classic');
</script>

<template>
    <!-- ============ CLASSIC ============ -->
    <section
        v-if="variant === 'classic'"
        class="relative flex min-h-screen flex-col items-center bg-[#f5f0e3] pt-6 text-[#2a2418] sm:justify-center sm:pt-0"
    >
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.5]"
            style="
                background-image: radial-gradient(circle at 1px 1px, #d9cdad 1px, transparent 0);
                background-size: 24px 24px;
            "
        ></div>

        <div class="relative z-10 flex w-full max-w-md items-center justify-end px-6">
            <button
                type="button"
                class="flex items-center gap-1 rounded-lg border border-[#d9cdad] bg-white/70 px-2.5 py-1 text-xs font-medium text-[#4a4235] backdrop-blur hover:bg-white"
                @click="variant = 'modern'"
            >
                Modern
                <AppIcon
                    name="arrowRight"
                    classes="[&>svg]:size-3.5 text-[#8f7a3c]"
                />
            </button>
        </div>

        <div class="relative z-10 mt-4">
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-[#8f7a3c]" />
            </Link>
        </div>

        <div
            class="relative z-10 mt-6 w-full overflow-hidden border border-[#e2d8be] bg-white/90 px-6 py-6 shadow-xl backdrop-blur sm:max-w-md sm:rounded-2xl"
        >
            <div class="mb-6 flex items-center gap-3 border-b border-[#ece4d0] pb-5">
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-[#8f7a3c]/10 text-[#8f7a3c]"
                >
                    <AppIcon
                        name="building"
                        classes="[&>svg]:size-5"
                    />
                </span>
                <div>
                    <p class="font-semibold text-[#1e1a12]">Heritage Hotels</p>
                    <p class="text-xs text-[#70685a]">Hotel Management Suite</p>
                </div>
            </div>

            <slot />
        </div>
    </section>

    <!-- ============ MODERN ============ -->
    <section
        v-else
        class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#0B1220] px-6 py-10 text-white"
    >
        <div
            class="pointer-events-none absolute -top-32 -right-32 h-96 w-96 rounded-full bg-indigo-600/30 blur-3xl"
        ></div>
        <div
            class="pointer-events-none absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"
        ></div>

        <div class="relative z-10 flex w-full max-w-md justify-end">
            <button
                type="button"
                class="flex items-center gap-1 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-medium text-white/70 backdrop-blur hover:text-white"
                @click="variant = 'classic'"
            >
                <AppIcon
                    name="sun"
                    classes="[&>svg]:size-3.5 text-indigo-300"
                />
                Classic
            </button>
        </div>

        <div
            class="relative z-10 mx-auto mt-4 w-full max-w-md rounded-2xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-xl"
        >
            <div class="mb-6 flex items-center gap-3">
                <span
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-500 text-white"
                >
                    <AppIcon
                        name="building"
                        classes="[&>svg]:size-5"
                    />
                </span>
                <span class="text-lg font-bold tracking-tight"
                    >heritage.io</span
                >
            </div>

            <slot />
        </div>
    </section>
</template>
