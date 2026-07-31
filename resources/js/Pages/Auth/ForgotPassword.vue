<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const page = usePage();
const form = useForm({ email: '' });

function submit() {
    form.post('/forgot-password');
}
</script>

<template>
    <Head title="Forgot password" />

    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Reset your password</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">We'll email you a link to reset it.</p>

    <div v-if="page.props.flash?.success" class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
        {{ page.props.flash.success }}
    </div>

    <form class="space-y-5" @submit.prevent="submit">
        <BaseInput v-model="form.email" type="email" label="Email" autofocus required :error="form.errors.email" />
        <BaseButton type="submit" class="w-full" :disabled="form.processing">Send reset link</BaseButton>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        <Link href="/login" class="font-medium text-brand-600 hover:text-brand-700">Back to login</Link>
    </p>
</template>
