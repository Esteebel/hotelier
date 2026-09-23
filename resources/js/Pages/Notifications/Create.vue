<script setup>
import { useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({ activeBranch: Object });

const form = useForm({
    title: "",
    message: "",
    type: "info",
});

function store() {
    form.post(route("notifications.store"));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                New Notification
            </h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form
                    @submit.prevent="store"
                    class="rounded-lg bg-white p-8 shadow-sm"
                >
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <InputLabel for="title" value="Title" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.title"
                            />
                        </div>
                        <div>
                            <InputLabel for="type" value="Type" />
                            <select
                                id="type"
                                v-model="form.type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="success">Success</option>
                                <option value="error">Error</option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.type"
                            />
                        </div>
                        <div>
                            <InputLabel for="message" value="Message" />
                            <TextAreaInput
                                id="message"
                                v-model="form.message"
                                class="mt-1 block w-full"
                                rows="4"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.message"
                            />
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-end gap-4">
                        <PrimaryButton :disabled="form.processing"
                            >Send</PrimaryButton
                        >
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
