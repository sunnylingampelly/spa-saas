<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const useRecoveryCode = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

function submit() {
    form.post('/two-factor-challenge');
}

function toggleRecovery() {
    useRecoveryCode.value = !useRecoveryCode.value;
    form.reset();
}
</script>

<template>
    <Head title="Two-factor authentication" />

    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Two-factor authentication</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">
        {{ useRecoveryCode ? 'Enter one of your recovery codes.' : 'Enter the code from your authenticator app.' }}
    </p>

    <form class="space-y-5" @submit.prevent="submit">
        <BaseInput
            v-if="!useRecoveryCode"
            v-model="form.code"
            label="Authentication code"
            autofocus
            required
            :error="form.errors.code"
        />
        <BaseInput
            v-else
            v-model="form.recovery_code"
            label="Recovery code"
            autofocus
            required
            :error="form.errors.recovery_code"
        />

        <BaseButton type="submit" class="w-full" :disabled="form.processing">Verify</BaseButton>
        <button type="button" class="w-full text-center text-sm text-slate-500 hover:text-slate-700" @click="toggleRecovery">
            {{ useRecoveryCode ? 'Use an authentication code instead' : 'Use a recovery code instead' }}
        </button>
    </form>
</template>
