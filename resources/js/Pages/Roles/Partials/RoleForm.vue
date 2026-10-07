<script setup>
import { useForm } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    role: {
        type: Object,
        default: null,
    },
    permissionGroups: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.role?.name ?? "",
    permissions: props.role?.permissions ?? [],
});

const submit = () => {
    if (props.role) {
        form.put(route("roles.update", props.role.id), {
            preserveScroll: true,
        });
    } else {
        form.post(route("roles.store"), {
            preserveScroll: true,
        });
    }
};

const cancel = () => {
    window.history.back();
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">Role Details</h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Define the role name used across the system.
            </p>

            <div class="mt-5 max-w-md">
                <InputLabel value="Role Name" />
                <TextInput
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="e.g. Front Desk Manager"
                    required
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.name" />

                <p
                    v-if="props.role && props.role.users_count > 0"
                    class="mt-2 text-xs text-gray-500"
                >
                    This role is assigned to
                    {{ props.role.users_count }}
                    {{ props.role.users_count === 1 ? "user" : "users" }}.
                    Renaming it will update the role shown on their profiles.
                </p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">Permissions</h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Assign which areas of the system this role can access.
            </p>

            <div
                v-for="group in permissionGroups"
                :key="group.label"
                class="mt-5"
            >
                <p
                    class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                >
                    {{ group.label }}
                </p>
                <div
                    class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <label
                        v-for="item in group.permissions"
                        :key="item.name"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        <input
                            v-model="form.permissions"
                            type="checkbox"
                            name="permissions[]"
                            :value="item.name"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span>{{ item.label }}</span>
                    </label>
                </div>
            </div>

            <InputError class="mt-4" :message="form.errors.permissions" />
        </div>

        <div class="flex items-center justify-end gap-3">
            <SecondaryButton type="button" @click="cancel">
                Cancel
            </SecondaryButton>
            <PrimaryButton type="submit" :disabled="form.processing">
                {{
                    form.processing
                        ? "Saving..."
                        : props.role
                          ? "Update Role"
                          : "Create Role"
                }}
            </PrimaryButton>
        </div>
    </form>
</template>
