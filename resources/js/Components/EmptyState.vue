<script setup>
import AppIcon from "@/Components/AppIcon.vue";

defineProps({
    title: { type: String, default: "No data found" },
    description: { type: String, default: "" },
    icon: { type: String, default: "search" },
});
</script>

<template>
    <div
        class="flex flex-col items-center justify-center px-6 py-14 text-center"
    >
        <span
            class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-300"
        >
            <AppIcon :name="icon" classes="[&>svg]:h-6 [&>svg]:w-6" />
        </span>
        <p class="mt-4 text-sm font-medium text-gray-900">{{ title }}</p>
        <p v-if="description" class="mt-1 text-sm text-gray-400">
            {{ description }}
        </p>
        <slot />
    </div>
</template>
