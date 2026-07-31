<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = useForm({ password: '' });

function submit() {
    form.post('/user/confirm-password', {
        onSuccess: () => {
            const redirectTo = new URLSearchParams(window.location.search).get('redirect');
            if (redirectTo) router.visit(redirectTo);
        },
        onFinish: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Confirm password" />

    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Confirm your password</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">This is a sensitive action — please confirm your password to continue.</p>

    <form class="space-y-5" @submit.prevent="submit">
        <BaseInput v-model="form.password" type="password" label="Password" autofocus required :error="form.errors.password" />
        <BaseButton type="submit" class="w-full" :disabled="form.processing">Confirm</BaseButton>
    </form>
</template>
