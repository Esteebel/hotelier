<script setup>
import { computed } from "vue";

const props = defineProps({
    menuItems: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["update:modelValue"]);

const quantityFor = (id) =>
    props.modelValue.find((row) => row.menu_item_id === id)?.quantity ?? 0;

const setQuantity = (item, quantity) => {
    quantity = Math.max(0, Math.min(99, Number(quantity) || 0));
    const rest = props.modelValue.filter((row) => row.menu_item_id !== item.id);
    emit(
        "update:modelValue",
        quantity > 0 ? [...rest, { menu_item_id: item.id, quantity }] : rest,
    );
};

const increment = (item) => setQuantity(item, quantityFor(item.id) + 1);
const decrement = (item) => setQuantity(item, quantityFor(item.id) - 1);

const grouped = computed(() => {
    const groups = {};
    for (const item of props.menuItems) {
        (groups[item.category] ??= []).push(item);
    }
    return groups;
});

const total = computed(() =>
    props.modelValue.reduce((sum, row) => {
        const item = props.menuItems.find((m) => m.id === row.menu_item_id);
        return item ? sum + Number(item.price) * row.quantity : sum;
    }, 0),
);

const selectedCount = computed(() =>
    props.modelValue.reduce((sum, row) => sum + row.quantity, 0),
);

const formatPrice = (price) => `₦${Number(price).toLocaleString()}`;
</script>

<template>
    <div>
        <div
            v-if="!menuItems.length"
            class="rounded-lg border border-dashed border-gray-300 px-6 py-10 text-center text-sm text-gray-500"
        >
            No menu items are available yet.
        </div>

        <div v-else class="space-y-6">
            <div v-for="(items, group) in grouped" :key="group">
                <h4
                    class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500"
                >
                    {{ group }}
                </h4>
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <ul class="divide-y divide-gray-100 bg-white">
                        <li
                            v-for="item in items"
                            :key="item.id"
                            class="flex items-center gap-4 px-4 py-3"
                            :class="{
                                'bg-indigo-50/40': quantityFor(item.id) > 0,
                            }"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium text-gray-900"
                                >
                                    {{ item.name }}
                                </p>
                                <p
                                    v-if="item.description"
                                    class="mt-0.5 truncate text-xs text-gray-500"
                                >
                                    {{ item.description }}
                                </p>
                            </div>
                            <span
                                class="whitespace-nowrap text-sm font-semibold text-gray-900"
                            >
                                {{ formatPrice(item.price) }}
                            </span>
                            <div
                                class="flex items-center gap-1"
                                role="group"
                                :aria-label="`Quantity for ${item.name}`"
                            >
                                <button
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40"
                                    :disabled="quantityFor(item.id) === 0"
                                    @click="decrement(item)"
                                >
                                    −
                                </button>
                                <span
                                    class="w-8 text-center text-sm font-medium text-gray-900"
                                >
                                    {{ quantityFor(item.id) }}
                                </span>
                                <button
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-50"
                                    @click="increment(item)"
                                >
                                    +
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3"
            >
                <span class="text-sm text-gray-600">
                    {{ selectedCount }}
                    item{{ selectedCount === 1 ? "" : "s" }} selected
                </span>
                <span class="text-sm font-semibold text-gray-900">
                    Total: {{ formatPrice(total) }}
                </span>
            </div>
        </div>
    </div>
</template>
