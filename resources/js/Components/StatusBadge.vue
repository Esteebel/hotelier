<script setup>
defineProps({
    value: { type: String, required: true },
    label: { type: String, default: null },
    variant: { type: String, default: "default" },
});

const variants = {
    default: "bg-gray-100 text-gray-700",
    success: "bg-emerald-50 text-emerald-700 ring-emerald-600/20",
    warning: "bg-amber-50 text-amber-700 ring-amber-600/20",
    danger: "bg-red-50 text-red-700 ring-red-600/20",
    info: "bg-blue-50 text-blue-700 ring-blue-600/20",
    purple: "bg-purple-50 text-purple-700 ring-purple-600/20",
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
        :class="variants[variant] ?? variants.default"
    >
        {{ label ?? value }}
    </span>
</template>
