<script setup>
import Modal from "@/Components/Modal.vue";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: "Confirm Action" },
    message: { type: String, default: "Are you sure you want to proceed?" },
    confirmText: { type: String, default: "Confirm" },
});

const emit = defineEmits(["confirm", "cancel"]);
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('cancel')">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
            <p class="mt-2 text-sm text-gray-600">{{ message }}</p>
            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton @click="emit('cancel')"
                    >Cancel</SecondaryButton
                >
                <DangerButton @click="emit('confirm')">{{
                    confirmText
                }}</DangerButton>
            </div>
        </div>
    </Modal>
</template>
