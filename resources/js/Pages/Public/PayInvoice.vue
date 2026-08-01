<script setup>
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';

const props = defineProps({
    invoice: { type: Object, required: true },
    spaName: { type: String, required: true },
    razorpayEnabled: { type: Boolean, required: true },
    razorpayKeyId: { type: String, default: null },
});

const rupees = (value) => `₹${Number(value).toLocaleString('en-IN')}`;

const paying = ref(false);
const paid = ref(props.invoice.balance_amount <= 0);
const errorMessage = ref(null);

onMounted(() => {
    if (props.razorpayEnabled) {
        const script = document.createElement('script');
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        document.head.appendChild(script);
    }
});

async function payOnline() {
    paying.value = true;
    errorMessage.value = null;

    try {
        const { data } = await axios.post(route('public.invoices.razorpay.order', props.invoice.public_token));

        const rzp = new window.Razorpay({
            key: data.key_id,
            order_id: data.order_id,
            amount: data.amount,
            currency: 'INR',
            name: data.spa_name,
            description: `Invoice ${data.invoice_number}`,
            handler: async (response) => {
                await axios.post(route('public.invoices.razorpay.verify', props.invoice.public_token), response);
                paid.value = true;
            },
            modal: { ondismiss: () => { paying.value = false; } },
        });
        rzp.open();
    } catch (e) {
        errorMessage.value = e.response?.data?.message ?? 'Something went wrong — please try again.';
    } finally {
        paying.value = false;
    }
}
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-white px-4 py-12 dark:bg-slate-950">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-brand-200/50 blur-3xl dark:bg-brand-900/20" />
            <div class="absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-brand-100/60 blur-3xl dark:bg-brand-800/10" />
            <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-rose-100/50 blur-3xl dark:bg-slate-800/20" />
        </div>

        <div class="relative z-10 w-full max-w-lg">
            <div class="mb-6 text-center">
                <p class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ spaName }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Invoice {{ invoice.invoice_number }}</p>
            </div>

            <div class="glass-panel rounded-2xl p-8">
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="item in invoice.items" :key="item.id" class="border-b border-slate-100 dark:border-slate-800">
                            <td class="py-2 text-slate-700 dark:text-slate-200">{{ item.description }} <span class="text-slate-400">× {{ item.quantity }}</span></td>
                            <td class="py-2 text-right text-slate-900 dark:text-white">{{ rupees(item.line_total) }}</td>
                        </tr>
                    </tbody>
                </table>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-semibold text-slate-900 dark:border-slate-700 dark:text-white">
                        <dt>Total</dt><dd>{{ rupees(invoice.total_amount) }}</dd>
                    </div>
                    <div v-if="invoice.paid_amount > 0" class="flex justify-between text-emerald-600">
                        <dt>Paid</dt><dd>{{ rupees(invoice.paid_amount) }}</dd>
                    </div>
                    <div class="flex justify-between font-medium text-slate-900 dark:text-white">
                        <dt>Balance due</dt><dd>{{ rupees(invoice.balance_amount) }}</dd>
                    </div>
                </dl>

                <div class="mt-6">
                    <div v-if="paid" class="rounded-xl bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400">
                        This invoice is fully paid — thank you!
                    </div>
                    <template v-else>
                        <BaseButton v-if="razorpayEnabled" class="w-full" :disabled="paying" @click="payOnline">
                            {{ paying ? 'Opening…' : `Pay ${rupees(invoice.balance_amount)} online` }}
                        </BaseButton>
                        <p v-else class="rounded-lg bg-slate-50 px-3 py-2 text-center text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            Online payment isn't available right now — please contact {{ spaName }} directly.
                        </p>
                        <p v-if="errorMessage" class="mt-2 text-center text-sm text-rose-600">{{ errorMessage }}</p>
                    </template>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">
                Powered by Spa<span class="font-medium">Orbit</span>
            </p>
        </div>
    </div>
</template>
