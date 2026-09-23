<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: "" },
});
</script>

<template>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">
                {{ title }}
            </h2>
            <p v-if="description" class="mt-1 text-sm text-gray-500">
                {{ description }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <slot name="actions" />
        </div>
    </div>
</template>
