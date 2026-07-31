<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Log in" />

    <h1 class="mb-6 text-xl font-semibold text-slate-900 dark:text-white">Welcome back</h1>

    <form class="space-y-5" @submit.prevent="submit">
        <BaseInput
            v-model="form.email"
            type="email"
            label="Email"
            autofocus
            required
            :error="form.errors.email"
        />
        <BaseInput
            v-model="form.password"
            type="password"
            label="Password"
            required
            :error="form.errors.password"
        />

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                Remember me
            </label>
            <Link href="/forgot-password" class="text-brand-600 hover:text-brand-700">Forgot password?</Link>
        </div>

        <BaseButton type="submit" class="w-full" :disabled="form.processing">Log in</BaseButton>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        Don't have an account?
        <Link href="/register" class="font-medium text-brand-600 hover:text-brand-700">Sign up</Link>
    </p>
</template>
