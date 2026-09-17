<script setup>
import { useForm } from "@inertiajs/vue3";
import AppIcon from "@/Components/AppIcon.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    roles: {
        type: Array,
        default: () => [],
    },
    branches: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.user?.name ?? "",
    email: props.user?.email ?? "",
    status: props.user?.status ?? "active",
    password: "",
    password_confirmation: "",
    roles: props.user?.roles ?? [],
    branches: props.user?.branches
        ? props.user.branches.map((branch) => branch.id)
        : [],
});

const isRoleSelected = (name) => form.roles.includes(name);

const toggleRole = (name) => {
    form.roles = isRoleSelected(name)
        ? form.roles.filter((role) => role !== name)
        : [...form.roles, name];
};

const isBranchSelected = (id) => form.branches.includes(id);

const toggleBranch = (id) => {
    form.branches = isBranchSelected(id)
        ? form.branches.filter((branchId) => branchId !== id)
        : [...form.branches, id];
};

const submit = () => {
    if (props.user) {
        form.put(route("users.update", props.user.id), {
            preserveScroll: true,
        });
    } else {
        form.post(route("users.store"), {
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
            <h3 class="text-sm font-semibold text-gray-900">
                Account Information
            </h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Core details for the user account.
            </p>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel value="Name" />
                    <TextInput
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. Jane Doe"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel value="Email" />
                    <TextInput
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        placeholder="jane@example.com"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel value="Status" />
                    <SelectInput
                        v-model="form.status"
                        class="mt-1 block w-full"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </SelectInput>
                    <InputError class="mt-2" :message="form.errors.status" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel value="Password" />
                    <TextInput
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        :required="!props.user"
                        placeholder="••••••••"
                        autocomplete="new-password"
                    />
                    <p v-if="props.user" class="mt-1 text-xs text-gray-500">
                        Leave blank to keep current password.
                    </p>
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel value="Confirm Password" />
                    <TextInput
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        placeholder="••••••••"
                        autocomplete="new-password"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.password_confirmation"
                    />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3
                class="flex items-center gap-2 text-sm font-semibold text-gray-900"
            >
                <AppIcon
                    name="briefcase"
                    classes="[&>svg]:h-4 [&>svg]:w-4 text-gray-400"
                />
                Roles & Permissions
            </h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Assign roles to control what this user can do.
            </p>

            <div class="mt-5 flex flex-wrap gap-2">
                <button
                    v-for="role in roles"
                    :key="role.name"
                    type="button"
                    @click="toggleRole(role.name)"
                    class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition"
                    :class="
                        isRoleSelected(role.name)
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'
                    "
                >
                    <span
                        class="flex h-4 w-4 items-center justify-center rounded border text-white"
                        :class="
                            isRoleSelected(role.name)
                                ? 'border-white/40 bg-white/20'
                                : 'border-gray-300 bg-white'
                        "
                    >
                        <AppIcon
                            v-if="isRoleSelected(role.name)"
                            name="check"
                            classes="[&>svg]:h-3 [&>svg]:w-3"
                        />
                    </span>
                    {{ role.label }}
                </button>
                <p v-if="!roles.length" class="text-sm text-gray-400">
                    No roles available.
                </p>
            </div>
        </div>

        <div
            v-if="branches.length"
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
        >
            <h3
                class="flex items-center gap-2 text-sm font-semibold text-gray-900"
            >
                <AppIcon
                    name="building"
                    classes="[&>svg]:h-4 [&>svg]:w-4 text-gray-400"
                />
                Branch Access
            </h3>
            <p class="mt-0.5 text-sm text-gray-500">
                Restrict this user to specific branches. Leave empty for no
                restrictions.
            </p>

            <div class="mt-5 flex flex-wrap gap-2">
                <button
                    v-for="branch in branches"
                    :key="branch.id"
                    type="button"
                    @click="toggleBranch(branch.id)"
                    class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition"
                    :class="
                        isBranchSelected(branch.id)
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'
                    "
                >
                    <span
                        class="flex h-4 w-4 items-center justify-center rounded border text-white"
                        :class="
                            isBranchSelected(branch.id)
                                ? 'border-white/40 bg-white/20'
                                : 'border-gray-300 bg-white'
                        "
                    >
                        <AppIcon
                            v-if="isBranchSelected(branch.id)"
                            name="check"
                            classes="[&>svg]:h-3 [&>svg]:w-3"
                        />
                    </span>
                    {{ branch.name }}
                </button>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <SecondaryButton type="button" @click="cancel">
                Cancel
            </SecondaryButton>
            <PrimaryButton type="submit" :disabled="form.processing">
                {{
                    form.processing
                        ? "Saving..."
                        : props.user
                          ? "Update User"
                          : "Create User"
                }}
            </PrimaryButton>
        </div>
    </form>
</template>
