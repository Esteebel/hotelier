import { ref, onMounted } from "vue";

export function useFlashMessage() {
    const message = ref("");
    const type = ref("success");
    const show = ref(false);
    let timeout = null;

    function flash(msg, msgType = "success") {
        message.value = msg;
        type.value = msgType;
        show.value = true;

        if (timeout) clearTimeout(timeout);
        timeout = setTimeout(() => {
            show.value = false;
        }, 4000);
    }

    function dismiss() {
        show.value = false;
        if (timeout) clearTimeout(timeout);
    }

    onMounted(() => {
        // Check for Inertia flash messages
        const pageProps = document.querySelector("[data-page]");
        if (pageProps) {
            try {
                const data = JSON.parse(pageProps.dataset.page);
                if (data.props?.flash?.success) {
                    flash(data.props.flash.success, "success");
                } else if (data.props?.flash?.error) {
                    flash(data.props.flash.error, "error");
                }
            } catch (e) {
                // Ignore parse errors
            }
        }
    });

    return { message, type, show, flash, dismiss };
}
