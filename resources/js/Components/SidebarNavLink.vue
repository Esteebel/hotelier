<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
});

const classes = computed(() =>
    props.active
        ? "flex items-center gap-3 rounded-lg bg-indigo-600/10 px-3 py-2 text-sm font-medium text-indigo-700 transition-colors"
        : "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900",
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot name="icon" />
        <slot />
    </Link>
</template>
