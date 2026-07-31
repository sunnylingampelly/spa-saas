<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const emailAlreadyRegistered = computed(() => form.errors.email?.includes('already registered'));

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Create your account" />

    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Create your spa account</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">Start your free trial — no card required.</p>

    <form class="space-y-5" @submit.prevent="submit">
        <BaseInput v-model="form.name" label="Full name" autofocus required :error="form.errors.name" />
        <div>
            <BaseInput v-model="form.email" type="email" label="Email" required :error="form.errors.email" />
            <p v-if="emailAlreadyRegistered" class="mt-1.5 text-sm">
                <Link href="/login" class="font-medium text-brand-600 hover:text-brand-700">Log in instead →</Link>
            </p>
        </div>
        <BaseInput v-model="form.password" type="password" label="Password" required :error="form.errors.password" />
        <BaseInput
            v-model="form.password_confirmation"
            type="password"
            label="Confirm password"
            required
            :error="form.errors.password_confirmation"
        />

        <BaseButton type="submit" class="w-full" :disabled="form.processing">Create account</BaseButton>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        Already have an account?
        <Link href="/login" class="font-medium text-brand-600 hover:text-brand-700">Log in</Link>
    </p>
</template>
