<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password');
}
</script>

<template>
    <Head title="Reset password" />

    <h1 class="mb-6 text-xl font-semibold text-slate-900 dark:text-white">Choose a new password</h1>

    <form class="space-y-5" @submit.prevent="submit">
        <BaseInput v-model="form.email" type="email" label="Email" required :error="form.errors.email" />
        <BaseInput v-model="form.password" type="password" label="New password" autofocus required :error="form.errors.password" />
        <BaseInput
            v-model="form.password_confirmation"
            type="password"
            label="Confirm new password"
            required
            :error="form.errors.password_confirmation"
        />

        <BaseButton type="submit" class="w-full" :disabled="form.processing">Reset password</BaseButton>
    </form>
</template>
