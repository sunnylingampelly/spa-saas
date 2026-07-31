<script setup>
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

const props = defineProps({
    twoFactorEnabled: { type: Boolean, required: true },
});

const step = ref(props.twoFactorEnabled ? 'done' : 'start');
const qrCodeSvg = ref('');
const secretKey = ref('');
const recoveryCodes = ref([]);
const code = ref('');
const error = ref('');
const loading = ref(false);

function handleError(err) {
    if (err.response?.status === 423) {
        const redirect = encodeURIComponent(route('admin.two-factor.setup'));
        window.location.href = `/user/confirm-password?redirect=${redirect}`;
        return;
    }
    error.value = err.response?.data?.message || 'Something went wrong — please try again.';
}

async function enable() {
    loading.value = true;
    error.value = '';
    try {
        await axios.post('/user/two-factor-authentication');
        const [qr, secret] = await Promise.all([
            axios.get('/user/two-factor-qr-code'),
            axios.get('/user/two-factor-secret-key'),
        ]);
        qrCodeSvg.value = qr.data.svg;
        secretKey.value = secret.data.secretKey;
        step.value = 'confirm';
    } catch (err) {
        handleError(err);
    } finally {
        loading.value = false;
    }
}

async function confirm() {
    loading.value = true;
    error.value = '';
    try {
        await axios.post('/user/confirmed-two-factor-authentication', { code: code.value });
        const recovery = await axios.get('/user/two-factor-recovery-codes');
        recoveryCodes.value = recovery.data;
        step.value = 'done';
    } catch (err) {
        error.value = err.response?.data?.errors?.code?.[0] || 'That code didn\'t match — please try again.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Head title="Set up two-factor authentication" />

    <div class="mx-auto max-w-lg">
        <h1 class="mb-2 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Secure your account</h1>
        <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">
            Two-factor authentication is required for the Super Admin account — it can approve real subscription payments across every spa.
        </p>

        <BaseCard v-if="step === 'start'">
            <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
                You'll need an authenticator app (Google Authenticator, Authy, 1Password, etc.) to scan a QR code.
            </p>
            <p v-if="error" class="mb-4 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
            <BaseButton class="w-full" :disabled="loading" @click="enable">Enable two-factor authentication</BaseButton>
        </BaseCard>

        <BaseCard v-else-if="step === 'confirm'" title="Scan this code">
            <div class="mb-4 flex justify-center rounded-lg bg-white p-4" v-html="qrCodeSvg" />
            <p class="mb-4 text-center text-xs text-slate-400">
                Can't scan it? Enter this key manually: <span class="font-mono">{{ secretKey }}</span>
            </p>
            <BaseInput v-model="code" label="Enter the 6-digit code from your app" placeholder="123456" :error="error" />
            <BaseButton class="mt-4 w-full" :disabled="loading || !code" @click="confirm">Confirm</BaseButton>
        </BaseCard>

        <BaseCard v-else title="Two-factor authentication is enabled">
            <p class="mb-4 text-sm text-emerald-600 dark:text-emerald-400">Your account is protected.</p>
            <template v-if="recoveryCodes.length">
                <p class="mb-2 text-sm text-slate-600 dark:text-slate-300">
                    Save these recovery codes somewhere safe — each can be used once if you lose access to your authenticator app.
                </p>
                <div class="mb-4 grid grid-cols-2 gap-2 rounded-lg bg-slate-50 p-3 font-mono text-xs dark:bg-slate-800">
                    <span v-for="rc in recoveryCodes" :key="rc">{{ rc }}</span>
                </div>
            </template>
            <BaseButton class="w-full" @click="router.visit(route('admin.dashboard'))">Continue to dashboard</BaseButton>
        </BaseCard>
    </div>
</template>
