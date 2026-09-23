import { ref } from "vue";

export function useConfirmDelete() {
    const showConfirm = ref(false);
    const deleteItem = ref(null);
    const deleteCallback = ref(null);

    function confirm(item, callback) {
        deleteItem.value = item;
        deleteCallback.value = callback;
        showConfirm.value = true;
    }

    function executeDelete() {
        if (deleteCallback.value) {
            deleteCallback.value(deleteItem.value);
        }
        cancel();
    }

    function cancel() {
        showConfirm.value = false;
        deleteItem.value = null;
        deleteCallback.value = null;
    }

    return { showConfirm, deleteItem, confirm, executeDelete, cancel };
}
