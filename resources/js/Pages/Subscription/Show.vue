<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    subscription: { type: Object, required: true },
    payments: { type: Array, required: true },
    plans: { type: Object, required: true },
    payout: { type: Object, required: true },
    payoutQrSvgs: { type: Object, required: true },
    razorpayEnabled: { type: Boolean, required: true },
    razorpayKeyId: { type: String, default: null },
    pendingManualPlanCodes: { type: Array, default: () => [] },
});

const rupees = (value) => `₹${Number(value).toLocaleString('en-IN')}`;

const statusBadge = computed(() => {
    const map = {
        trialing: { color: 'brand', label: 'Trial' },
        active: { color: 'green', label: 'Active' },
        past_due: { color: 'amber', label: 'Past due' },
        cancelled: { color: 'rose', label: 'Cancelled' },
    };
    return map[props.subscription.status] ?? { color: 'slate', label: props.subscription.status };
});

const paymentStatusBadge = { pending: 'amber', paid: 'green', failed: 'rose', refunded: 'slate' };

const razorpayLoading = ref(null);
const manualFormOpenFor = ref(null);
const errorMessage = ref(null);
const errorPlanCode = ref(null);
let razorpayScriptReady = null;

function loadRazorpayScript() {
    if (!razorpayScriptReady) {
        razorpayScriptReady = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://checkout.razorpay.com/v1/checkout.js';
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Could not reach the Razorpay checkout script.'));
            document.head.appendChild(script);
        });
    }
    return razorpayScriptReady;
}

onMounted(() => {
    if (props.razorpayEnabled) {
        loadRazorpayScript();
    }
});

async function payOnline(planCode) {
    razorpayLoading.value = planCode;
    errorMessage.value = null;
    errorPlanCode.value = null;

    try {
        const [{ data }] = await Promise.all([
            axios.post(route('subscription.razorpay.order'), { plan: planCode }),
            loadRazorpayScript(),
        ]);

        const rzp = new window.Razorpay({
            key: data.key_id,
            order_id: data.order_id,
            amount: data.amount,
            currency: 'INR',
            name: 'SpaOrbit',
            description: `${data.plan_label} plan — ${data.spa_name}`,
            handler: async (response) => {
                await axios.post(route('subscription.razorpay.verify'), response);
                router.visit(route('subscription.show'));
            },
            modal: { ondismiss: () => { razorpayLoading.value = null; } },
        });
        rzp.open();
    } catch (e) {
        errorMessage.value = e.response?.data?.message ?? e.message ?? 'Something went wrong — please try again.';
        errorPlanCode.value = planCode;
    } finally {
        razorpayLoading.value = null;
    }
}

const manualForm = useForm({ plan: null, proof_note: '' });

function submitManual(planCode) {
    manualForm.plan = planCode;
    manualForm.post(route('subscription.manual'), {
        preserveScroll: true,
        onSuccess: () => {
            manualForm.reset();
            manualFormOpenFor.value = null;
        },
    });
}
</script>

<template>
    <Head title="Subscription" />

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Subscription</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage your SpaOrbit plan and payments.</p>
        </div>
        <BaseBadge :color="statusBadge.color">{{ statusBadge.label }}</BaseBadge>
    </div>

    <BaseCard class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Current plan</p>
                <p class="text-lg font-semibold text-slate-900 dark:text-white capitalize">{{ subscription.plan_code }}</p>
            </div>
            <div v-if="subscription.status === 'trialing'">
                <p class="text-sm text-slate-500 dark:text-slate-400">Trial ends</p>
                <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ formatDate(subscription.current_period_ends_at) }}</p>
            </div>
            <div v-else-if="subscription.current_period_ends_at">
                <p class="text-sm text-slate-500 dark:text-slate-400">Renews on</p>
                <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ formatDate(subscription.current_period_ends_at) }}</p>
            </div>
            <div v-else-if="subscription.plan_code === 'lifetime'">
                <p class="text-sm text-slate-500 dark:text-slate-400">Access</p>
                <p class="text-lg font-semibold text-emerald-600">Lifetime — never expires</p>
            </div>
        </div>
    </BaseCard>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <BaseCard v-for="(plan, code) in plans" :key="code" :title="`${plan.label} plan`">
            <p class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">
                {{ rupees(plan.price) }}
                <span class="text-base font-normal text-slate-400">{{ plan.cycle === 'monthly' ? '/month' : ' one-time' }}</span>
            </p>

            <BaseButton
                v-if="razorpayEnabled"
                class="mt-4 w-full"
                :disabled="razorpayLoading === code"
                @click="payOnline(code)"
            >
                {{ razorpayLoading === code ? 'Opening…' : 'Pay online (Card / UPI / Netbanking)' }}
            </BaseButton>
            <p v-if="errorPlanCode === code" class="mt-2 text-center text-sm text-rose-600 dark:text-rose-400">
                {{ errorMessage }}
            </p>
            <p v-if="!razorpayEnabled" class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                Online payment isn't set up yet — use UPI / bank transfer below.
            </p>

            <p v-if="pendingManualPlanCodes.includes(code)" class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                Your UPI / bank transfer payment is awaiting confirmation from the SpaOrbit team.
            </p>

            <template v-else>
                <button
                    class="mt-3 w-full text-sm font-medium text-brand-600 hover:text-brand-700"
                    @click="manualFormOpenFor = manualFormOpenFor === code ? null : code"
                >
                    {{ manualFormOpenFor === code ? 'Hide UPI / bank transfer details' : 'Pay via UPI / bank transfer instead' }}
                </button>

                <div v-if="manualFormOpenFor === code" class="mt-4 space-y-4 rounded-xl border border-slate-100 p-4 dark:border-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="rounded-xl bg-white p-2" v-html="payoutQrSvgs[code]" />
                        <div class="text-sm">
                            <p class="text-slate-500 dark:text-slate-400">UPI ID</p>
                            <p class="font-medium text-slate-900 dark:text-white">{{ payout.upi_id }}</p>
                            <p class="mt-2 text-slate-500 dark:text-slate-400">Account name</p>
                            <p class="font-medium text-slate-900 dark:text-white">{{ payout.account_name }}</p>
                        </div>
                    </div>
                    <BaseTextarea
                        v-model="manualForm.proof_note"
                        label="Reference / note (optional)"
                        placeholder="e.g. UPI transaction ID"
                    />
                    <BaseButton variant="secondary" class="w-full" :disabled="manualForm.processing" @click="submitManual(code)">
                        I've made the payment
                    </BaseButton>
                </div>
            </template>
        </BaseCard>
    </div>

    <BaseCard title="Payment history">
        <p v-if="!payments.length" class="text-sm text-slate-500 dark:text-slate-400">No payments yet.</p>
        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                    <th class="pb-2 font-medium">Date</th>
                    <th class="pb-2 font-medium">Plan</th>
                    <th class="pb-2 font-medium">Method</th>
                    <th class="pb-2 font-medium">Amount</th>
                    <th class="pb-2 font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="payment in payments" :key="payment.id" class="border-b border-slate-50 dark:border-slate-800/60">
                    <td class="py-2 text-slate-600 dark:text-slate-300">{{ formatDate(payment.created_at) }}</td>
                    <td class="py-2 capitalize text-slate-600 dark:text-slate-300">{{ payment.plan_code }}</td>
                    <td class="py-2 capitalize text-slate-600 dark:text-slate-300">{{ payment.method }}</td>
                    <td class="py-2 text-slate-900 dark:text-white">{{ rupees(payment.amount) }}</td>
                    <td class="py-2"><BaseBadge :color="paymentStatusBadge[payment.status]">{{ payment.status }}</BaseBadge></td>
                </tr>
            </tbody>
        </table>
    </BaseCard>
</template>
