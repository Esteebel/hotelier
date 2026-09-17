<script setup>
import AppIcon from "@/Components/AppIcon.vue";
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const variant = ref("modern");

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Sign in" />

    <div
        class="relative min-h-screen bg-[#0B1220] text-white overflow-hidden w-full"
    >
        <div
            class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-indigo-600/30 blur-3xl pointer-events-none"
        ></div>
        <div
            class="absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl pointer-events-none"
        ></div>

        <div
            class="absolute top-6 right-6 z-20 flex items-center gap-1 rounded-lg border border-white/10 bg-white/5 p-1 backdrop-blur"
        >
            <button
                type="button"
                class="rounded-md px-2.5 py-1 text-xs font-medium"
                :class="
                    variant === 'classic'
                        ? 'bg-white text-gray-900'
                        : 'text-white/60 hover:text-white'
                "
                @click="variant = 'classic'"
            >
                Classic
            </button>
            <button
                type="button"
                class="rounded-md px-2.5 py-1 text-xs font-medium"
                :class="
                    variant === 'modern'
                        ? 'bg-white text-gray-900'
                        : 'text-white/60 hover:text-white'
                "
                @click="variant = 'modern'"
            >
                Modern
            </button>
        </div>

        <!-- MODERN SPLIT-SCREEN -->
        <div
            v-if="variant === 'modern'"
            class="relative z-10 mx-auto flex h-screen max-h-[100dvh] w-full max-w-6xl items-center gap-12 px-6 lg:px-10"
        >
            <div class="hidden flex-1 lg:block">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-500 text-white"
                    >
                        <AppIcon
                            name="building"
                            classes="[&>svg]:h-6 [&>svg]:w-6"
                        />
                    </span>
                    <span class="text-xl font-bold tracking-tight"
                        >Heritage Hotels</span
                    >
                </div>

                <p
                    class="mt-10 text-sm tracking-[0.2em] text-indigo-300 uppercase"
                >
                    Hotel Management Suite
                </p>
                <h1 class="mt-4 text-4xl leading-tight font-extrabold">
                    One home for every<br />room, stay & branch.
                </h1>
                <p class="mt-6 max-w-md text-sm leading-relaxed text-gray-300">
                    Organizations, branches, room types and rooms — with
                    role-based access for every team. Built for the modern front
                    desk.
                </p>

                <div class="mt-10 grid max-w-md grid-cols-3 gap-6">
                    <div>
                        <p class="text-2xl font-bold text-white">4</p>
                        <p class="mt-1 text-xs text-gray-400">Core modules</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">8</p>
                        <p class="mt-1 text-xs text-gray-400">Staff roles</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">∞</p>
                        <p class="mt-1 text-xs text-gray-400">Branches</p>
                    </div>
                </div>
            </div>

            <div
                class="w-full max-w-md rounded-2xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-xl mx-auto lg:mx-0"
            >
                <h2 class="text-2xl font-bold text-white">Welcome back</h2>
                <p class="mt-1 text-sm text-gray-400">
                    Sign in to your property dashboard.
                </p>

                <form @submit.prevent="submit" class="mt-8 space-y-5">
                    <div>
                        <InputLabel
                            for="email"
                            value="Email"
                            class="text-gray-300"
                        />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1.5 block w-full border-white/10 bg-white/5 text-white placeholder:text-gray-500"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <InputLabel
                                for="password"
                                value="Password"
                                class="text-gray-300"
                            />
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs text-indigo-300 hover:text-indigo-200"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1.5 block w-full border-white/10 bg-white/5 text-white placeholder:text-gray-500"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.password"
                        />
                    </div>

                    <label
                        class="flex items-center gap-2 text-sm text-gray-300"
                    >
                        <Checkbox
                            name="remember"
                            v-model:checked="form.remember"
                            class="rounded border-white/20 bg-white/10"
                        />
                        Remember me
                    </label>

                    <PrimaryButton
                        class="w-full justify-center rounded-xl bg-indigo-500 py-3 text-white"
                        :class="{ 'opacity-60': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? "Signing in…" : "Sign in" }}
                    </PrimaryButton>
                </form>

                <p class="mt-6 text-center text-sm text-gray-400">
                    New to Heritage Hotels?
                    <Link
                        :href="route('register')"
                        class="font-medium text-indigo-300 hover:text-indigo-200"
                    >
                        Create an account
                    </Link>
                </p>
            </div>
        </div>

        <!-- CLASSIC CENTERED CARD -->
        <GuestLayout v-else :show-toggle="false">
            <div class="flex items-center gap-3">
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-[#8f7a3c] text-white"
                >
                    <AppIcon
                        name="building"
                        classes="[&>svg]:h-5 [&>svg]:w-5"
                    />
                </span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        Heritage Hotels
                    </h2>
                    <p class="text-xs text-gray-500">Hotel Management Suite</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="mt-8 space-y-5">
                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1.5 block w-full"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <InputLabel for="password" value="Password" />
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs text-[#8f7a3c] underline hover:text-[#72632f]"
                        >
                            Forgot password?
                        </Link>
                    </div>
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1.5 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    Remember me
                </label>

                <PrimaryButton
                    class="w-full justify-center bg-[#8f7a3c] py-3"
                    :class="{ 'opacity-60': form.processing }"
                    :disabled="form.processing"
                >
                    Sign in
                </PrimaryButton>

                <p class="text-center text-sm text-gray-500">
                    New here?
                    <Link
                        :href="route('register')"
                        class="font-medium text-[#8f7a3c] underline hover:text-[#72632f]"
                    >
                        Create an account
                    </Link>
                </p>
            </form>
        </GuestLayout>
    </div>
</template>
