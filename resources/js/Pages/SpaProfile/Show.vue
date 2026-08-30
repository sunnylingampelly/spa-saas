<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';
import { ref } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import { useConfirm } from '../../Composables/useConfirm';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { confirmDialog } = useConfirm();

const props = defineProps({
    spa: { type: Object, required: true },
    razorpayKeyId: { type: String, default: null },
    razorpayConfigured: { type: Boolean, required: true },
    razorpayWebhookUrl: { type: String, required: true },
    smtpConfigured: { type: Boolean, required: true },
});

const tabs = ['General', 'Payment Gateway', 'Email (SMTP)'];

// --- General ---
const form = useForm({
    name: props.spa.name,
    legal_business_name: props.spa.legal_business_name,
    gst_number: props.spa.gst_number,
    pan_number: props.spa.pan_number,
    phone: props.spa.phone,
    email: props.spa.email,
    address_line_1: props.spa.address_line_1,
    address_line_2: props.spa.address_line_2,
    city: props.spa.city,
    state: props.spa.state,
    pincode: props.spa.pincode,
    google_maps_link: props.spa.google_maps_link,
    opening_time: props.spa.opening_time,
    closing_time: props.spa.closing_time,
    invoice_prefix: props.spa.invoice_prefix,
    invoice_footer_note: props.spa.invoice_footer_note,
});

function submit() {
    form.put('/spa/profile');
}

// --- Payment gateway ---
const paymentForm = useForm({
    razorpay_key_id: props.razorpayKeyId,
    razorpay_key_secret: '',
    razorpay_webhook_secret: '',
});

function submitPaymentSettings() {
    paymentForm.put('/spa/payment-settings', {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.razorpay_key_secret = '';
            paymentForm.razorpay_webhook_secret = '';
        },
    });
}

async function disconnectRazorpay() {
    const confirmed = await confirmDialog({
        title: 'Disconnect Razorpay?',
        message: 'Your customer pay links will stop accepting online payments until you reconnect.',
        confirmLabel: 'Disconnect',
        danger: true,
    });
    if (!confirmed) return;

    useForm({}).delete('/spa/payment-settings', { preserveScroll: true });
}

const webhookUrlCopied = ref(false);

async function copyWebhookUrl() {
    await navigator.clipboard.writeText(props.razorpayWebhookUrl);
    webhookUrlCopied.value = true;
    setTimeout(() => { webhookUrlCopied.value = false; }, 2000);
}

// --- Email (SMTP) ---
const encryptionOptions = [
    { value: '', label: 'None' },
    { value: 'tls', label: 'TLS' },
    { value: 'ssl', label: 'SSL' },
];

const emailForm = useForm({
    smtp_host: props.spa.smtp_host,
    smtp_port: props.spa.smtp_port,
    smtp_username: props.spa.smtp_username,
    smtp_password: '',
    smtp_encryption: props.spa.smtp_encryption ?? '',
    mail_from_address: props.spa.mail_from_address,
    mail_from_name: props.spa.mail_from_name,
});

function submitEmailSettings() {
    emailForm.put('/spa/email-settings', {
        preserveScroll: true,
        onSuccess: () => { emailForm.smtp_password = ''; },
    });
}

async function disconnectEmail() {
    const confirmed = await confirmDialog({
        title: 'Disconnect custom SMTP?',
        message: "Campaign emails will send through SpaOrbit's own mailer again instead of your account.",
        confirmLabel: 'Disconnect',
        danger: true,
    });
    if (!confirmed) return;

    useForm({}).delete('/spa/email-settings', { preserveScroll: true });
}

const testEmailForm = useForm({});
function sendTestEmail() {
    testEmailForm.post('/spa/email-settings/test', { preserveScroll: true });
}
</script>

<template>
    <Head title="Settings" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Settings</h1>

    <TabGroup>
        <TabList class="mb-6 flex gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-800/60">
            <Tab
                v-for="tab in tabs"
                :key="tab"
                v-slot="{ selected }"
                as="template"
            >
                <button
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition focus:outline-none"
                    :class="selected
                        ? 'bg-white text-brand-700 shadow-sm dark:bg-slate-900 dark:text-white'
                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                >
                    {{ tab }}
                </button>
            </Tab>
        </TabList>

        <TabPanels>
            <!-- General -->
            <TabPanel>
                <form class="space-y-6" @submit.prevent="submit">
                    <BaseCard title="Business details">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <BaseInput v-model="form.name" label="Spa name" required :error="form.errors.name" />
                            <BaseInput v-model="form.legal_business_name" label="Legal business name" :error="form.errors.legal_business_name" />
                            <BaseInput v-model="form.gst_number" label="GST number" :error="form.errors.gst_number" />
                            <BaseInput v-model="form.pan_number" label="PAN number" :error="form.errors.pan_number" />
                        </div>
                    </BaseCard>

                    <BaseCard title="Contact & location">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <BaseInput v-model="form.phone" label="Phone" :error="form.errors.phone" />
                            <BaseInput v-model="form.email" type="email" label="Email" :error="form.errors.email" />
                            <BaseInput v-model="form.address_line_1" label="Address line 1" :error="form.errors.address_line_1" />
                            <BaseInput v-model="form.address_line_2" label="Address line 2" :error="form.errors.address_line_2" />
                            <BaseInput v-model="form.city" label="City" :error="form.errors.city" />
                            <BaseInput v-model="form.state" label="State" :error="form.errors.state" />
                            <BaseInput v-model="form.pincode" label="Pincode" :error="form.errors.pincode" />
                            <BaseInput v-model="form.google_maps_link" label="Google Maps link" :error="form.errors.google_maps_link" />
                        </div>
                    </BaseCard>

                    <BaseCard title="Hours & invoicing">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <BaseInput v-model="form.opening_time" type="time" label="Opening time" :error="form.errors.opening_time" />
                            <BaseInput v-model="form.closing_time" type="time" label="Closing time" :error="form.errors.closing_time" />
                            <BaseInput v-model="form.invoice_prefix" label="Invoice prefix" :error="form.errors.invoice_prefix" />
                            <BaseInput v-model="form.invoice_footer_note" label="Invoice footer note" :error="form.errors.invoice_footer_note" />
                        </div>
                    </BaseCard>

                    <BaseButton type="submit" :disabled="form.processing">Save changes</BaseButton>
                </form>
            </TabPanel>

            <!-- Payment Gateway -->
            <TabPanel>
                <form @submit.prevent="submitPaymentSettings">
                    <BaseCard title="Payment gateway">
                        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                            Connect your own Razorpay account so customer invoice payments go straight to you — the platform
                            never sees or touches this money.
                        </p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <BaseInput v-model="paymentForm.razorpay_key_id" label="Key ID" :error="paymentForm.errors.razorpay_key_id" />
                            <div />
                            <BaseInput
                                v-model="paymentForm.razorpay_key_secret"
                                type="password"
                                label="Key Secret"
                                :placeholder="razorpayConfigured ? 'Leave blank to keep your current secret' : ''"
                                :error="paymentForm.errors.razorpay_key_secret"
                            />
                            <BaseInput
                                v-model="paymentForm.razorpay_webhook_secret"
                                type="password"
                                label="Webhook Secret"
                                :placeholder="razorpayConfigured ? 'Leave blank to keep your current secret' : ''"
                                :error="paymentForm.errors.razorpay_webhook_secret"
                            />
                        </div>

                        <div class="mt-4">
                            <label class="form-label">Webhook URL</label>
                            <div class="flex gap-2">
                                <input :value="razorpayWebhookUrl" readonly class="form-input flex-1 text-sm text-slate-500 dark:text-slate-400" />
                                <BaseButton type="button" variant="secondary" @click="copyWebhookUrl">{{ webhookUrlCopied ? 'Copied!' : 'Copy' }}</BaseButton>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-400">
                                Paste this into your Razorpay Dashboard → Settings → Webhooks, with the "payment.captured" event enabled.
                            </p>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <BaseButton type="submit" :disabled="paymentForm.processing">Save Payment Settings</BaseButton>
                            <BaseButton v-if="razorpayConfigured" type="button" variant="danger" @click="disconnectRazorpay">Disconnect Razorpay</BaseButton>
                        </div>
                    </BaseCard>
                </form>
            </TabPanel>

            <!-- Email (SMTP) -->
            <TabPanel>
                <form @submit.prevent="submitEmailSettings">
                    <BaseCard title="Email (SMTP)">
                        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                            Connect your own SMTP account so email campaigns send from your own address instead of
                            SpaOrbit's shared mailer. Leave this blank to keep using the platform's mailer.
                        </p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <BaseInput v-model="emailForm.smtp_host" label="SMTP host" placeholder="smtp.example.com" :error="emailForm.errors.smtp_host" />
                            <BaseInput v-model="emailForm.smtp_port" label="Port" placeholder="587" :error="emailForm.errors.smtp_port" />
                            <BaseInput v-model="emailForm.smtp_username" label="Username" :error="emailForm.errors.smtp_username" />
                            <BaseInput
                                v-model="emailForm.smtp_password"
                                type="password"
                                label="Password"
                                :placeholder="smtpConfigured ? 'Leave blank to keep your current password' : ''"
                                :error="emailForm.errors.smtp_password"
                            />
                            <BaseListbox v-model="emailForm.smtp_encryption" label="Encryption" :options="encryptionOptions" :error="emailForm.errors.smtp_encryption" />
                            <div />
                            <BaseInput v-model="emailForm.mail_from_address" type="email" label="From address" :error="emailForm.errors.mail_from_address" />
                            <BaseInput v-model="emailForm.mail_from_name" label="From name" :placeholder="spa.name" :error="emailForm.errors.mail_from_name" />
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <BaseButton type="submit" :disabled="emailForm.processing">Save Email Settings</BaseButton>
                            <BaseButton
                                type="button"
                                variant="secondary"
                                :disabled="!smtpConfigured || testEmailForm.processing"
                                @click="sendTestEmail"
                            >
                                Send Test Email
                            </BaseButton>
                            <BaseButton v-if="smtpConfigured" type="button" variant="danger" @click="disconnectEmail">Disconnect SMTP</BaseButton>
                        </div>
                    </BaseCard>
                </form>
            </TabPanel>
        </TabPanels>
    </TabGroup>
</template>
