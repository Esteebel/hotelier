<script setup>
import { ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";

const page = usePage();
const visible = ref(false);
const message = ref("");
const type = ref("success");

const icons = {
    success: "checkCircle",
    error: "xCircle",
    warning: "alertTriangle",
    info: "info",
};

const styles = {
    success: "bg-emerald-50 text-emerald-800 border-emerald-200",
    error: "bg-red-50 text-red-800 border-red-200",
    warning: "bg-amber-50 text-amber-800 border-amber-200",
    info: "bg-blue-50 text-blue-800 border-blue-200",
};

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            message.value = flash.success;
            type.value = "success";
            visible.value = true;
        } else if (flash?.error) {
            message.value = flash.error;
            type.value = "error";
            visible.value = true;
        } else if (flash?.warning) {
            message.value = flash.warning;
            type.value = "warning";
            visible.value = true;
        } else if (flash?.message) {
            message.value = flash.message;
            type.value = flash.type ?? "info";
            visible.value = true;
        }

        if (visible.value) {
            setTimeout(() => {
                visible.value = false;
            }, 4000);
        }
    },
    { immediate: true, deep: true },
);

function close() {
    visible.value = false;
}
</script>

<template>
    <Transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div
            v-if="visible && message"
            class="pointer-events-auto fixed right-4 top-4 z-[100] max-w-sm rounded-lg border p-4 shadow-lg"
            :class="styles[type]"
        >
            <div class="flex items-start gap-3">
                <AppIcon
                    :name="icons[type]"
                    classes="[&>svg]:h-5 [&>svg]:w-5 mt-0.5 flex-shrink-0"
                />
                <p class="flex-1 text-sm font-medium">{{ message }}</p>
                <button
                    @click="close"
                    class="flex-shrink-0 rounded p-0.5 hover:bg-black/5"
                >
                    <AppIcon name="x" classes="[&>svg]:h-4 [&>svg]:w-4" />
                </button>
            </div>
        </div>
    </Transition>
</template>
