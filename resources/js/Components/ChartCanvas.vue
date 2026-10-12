<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Chart, registerables } from "chart.js";

Chart.register(...registerables);

const props = defineProps({
    type: { type: String, required: true },
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
});

const canvas = ref(null);
let chart = null;

const render = () => {
    if (!canvas.value || !props.data) return;

    chart?.destroy();
    chart = new Chart(canvas.value, {
        type: props.type,
        data: props.data,
        options: props.options,
    });
};

onMounted(render);
watch(() => props.data, render, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <canvas ref="canvas"></canvas>
</template>
