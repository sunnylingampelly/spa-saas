<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const walletTypeOptions = [
    { value: 'credit', label: 'Credit (top-up)' },
    { value: 'debit', label: 'Debit (redeem)' },
];

const props = defineProps({
    customer: { type: Object, required: true },
    qrCodeSvg: { type: String, required: true },
    history: { type: Object, required: true },
});

const appointmentStatusColor = (status) => ({
    booked: 'slate',
    confirmed: 'brand',
    in_progress: 'amber',
    completed: 'green',
    cancelled: 'rose',
    no_show: 'rose',
}[status] ?? 'slate');

const invoiceStatusColor = (status) => ({
    unpaid: 'rose',
    partially_paid: 'amber',
    paid: 'green',
    refunded: 'slate',
    cancelled: 'slate',
}[status] ?? 'slate');

const showWalletForm = ref(false);
const walletForm = useForm({ type: 'credit', amount: '', reason: '' });

function submitWallet() {
    walletForm.post(route('customers.wallet.store', props.customer.id), {
        preserveScroll: true,
        onSuccess: () => { showWalletForm.value = false; walletForm.reset(); },
    });
}
</script>

<template>
    <Head :title="customer.name" />

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">
                {{ customer.name }}
                <BaseBadge v-if="customer.is_vip" color="brand" class="ml-2">VIP</BaseBadge>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ customer.customer_code }} · Customer since {{ formatDate(customer.customer_since) }}
            </p>
        </div>
        <Link :href="route('customers.edit', customer.id)"><BaseButton variant="secondary">Edit</BaseButton></Link>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <BaseCard title="Profile">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd>{{ customer.phone || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">WhatsApp</dt><dd>{{ customer.whatsapp_number || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd>{{ customer.email || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">DOB</dt><dd>{{ formatDate(customer.date_of_birth) || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Anniversary</dt><dd>{{ formatDate(customer.anniversary_date) || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Preferred massage</dt><dd>{{ customer.preferred_service?.name || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Preferred therapist</dt><dd>{{ customer.preferred_employee?.name || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Referred by</dt><dd>{{ customer.referred_by?.name || '—' }}</dd></div>
            </dl>
            <div v-if="customer.medical_notes || customer.allergy_notes" class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm dark:border-slate-800">
                <p v-if="customer.medical_notes"><span class="font-medium text-slate-700 dark:text-slate-300">Medical:</span> {{ customer.medical_notes }}</p>
                <p v-if="customer.allergy_notes"><span class="font-medium text-slate-700 dark:text-slate-300">Allergies:</span> {{ customer.allergy_notes }}</p>
            </div>
        </BaseCard>

        <BaseCard title="Customer QR">
            <div class="flex flex-col items-center gap-3 py-2">
                <div class="rounded-xl bg-white p-3" v-html="qrCodeSvg" />
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ customer.customer_code }}</p>
                <p class="text-xs text-slate-400">Referral code: <span class="font-mono">{{ customer.referral_code }}</span></p>
            </div>
        </BaseCard>

        <BaseCard title="Wallet & rewards">
            <template #actions>
                <button class="text-sm font-medium text-brand-600 hover:text-brand-700" @click="showWalletForm = !showWalletForm">
                    + Adjust wallet
                </button>
            </template>

            <div class="mb-4 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-slate-50 px-3 py-2 text-center dark:bg-slate-800/60">
                    <p class="text-lg font-semibold text-slate-900 dark:text-white">₹{{ customer.wallet_balance }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Wallet balance</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2 text-center dark:bg-slate-800/60">
                    <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ customer.reward_points }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Reward points</p>
                </div>
            </div>

            <form v-if="showWalletForm" class="mb-4 space-y-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60" @submit.prevent="submitWallet">
                <BaseListbox v-model="walletForm.type" :options="walletTypeOptions" />
                <BaseInput v-model="walletForm.amount" type="number" placeholder="Amount (₹)" required :error="walletForm.errors.amount" />
                <BaseInput v-model="walletForm.reason" type="text" placeholder="Reason (optional)" />
                <BaseButton type="submit" :disabled="walletForm.processing">Save</BaseButton>
            </form>

            <ul class="max-h-48 space-y-1 overflow-y-auto text-sm">
                <li v-for="t in customer.wallet_transactions" :key="t.id" class="flex justify-between border-b border-slate-100 py-1 dark:border-slate-800">
                    <span>{{ formatDate(t.created_at, { withTime: true }) }}</span>
                    <span :class="t.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'">
                        {{ t.type === 'credit' ? '+' : '-' }}₹{{ t.amount }}
                    </span>
                </li>
                <li v-if="customer.wallet_transactions.length === 0" class="text-slate-500">No wallet activity yet.</li>
            </ul>

            <p class="mb-2 mt-4 border-t border-slate-100 pt-4 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:border-slate-800">
                Reward points
            </p>
            <ul class="max-h-40 space-y-1 overflow-y-auto text-sm">
                <li v-for="t in customer.reward_point_transactions" :key="t.id" class="flex justify-between border-b border-slate-100 py-1 dark:border-slate-800">
                    <span>{{ formatDate(t.created_at, { withTime: true }) }} · {{ t.reason }}</span>
                    <span class="text-emerald-600">+{{ t.points }}</span>
                </li>
                <li v-if="customer.reward_point_transactions.length === 0" class="text-slate-500">No points earned yet.</li>
            </ul>
        </BaseCard>
    </div>

    <BaseCard title="Visit history" class="mt-6">
        <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl bg-slate-50 px-3 py-2 text-center dark:bg-slate-800/60">
                <p class="text-lg font-semibold text-slate-900 dark:text-white">₹{{ history.stats.lifetimeSpend.toFixed(2) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Lifetime spend</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2 text-center dark:bg-slate-800/60">
                <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ history.stats.averageBill !== null ? `₹${history.stats.averageBill.toFixed(2)}` : '—' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Average bill</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2 text-center dark:bg-slate-800/60">
                <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ history.stats.visitCount }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Completed visits</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2 text-center dark:bg-slate-800/60">
                <p class="text-lg font-semibold text-slate-900 dark:text-white">
                    {{ history.stats.visitFrequencyDays !== null ? `Every ${history.stats.visitFrequencyDays}d` : 'Not enough visits yet' }}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Visit frequency</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Recent appointments</p>
                <ul class="max-h-56 space-y-1 overflow-y-auto text-sm">
                    <li v-for="a in history.recentAppointments" :key="a.id" class="flex items-center justify-between border-b border-slate-100 py-1.5 dark:border-slate-800">
                        <span>{{ formatDate(a.starts_at, { withTime: true }) }} · {{ a.service?.name }}</span>
                        <BaseBadge :color="appointmentStatusColor(a.status)">{{ a.status.replace('_', ' ') }}</BaseBadge>
                    </li>
                    <li v-if="history.recentAppointments.length === 0" class="text-slate-500">No appointments yet.</li>
                </ul>
            </div>
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Recent bills</p>
                <ul class="max-h-56 space-y-1 overflow-y-auto text-sm">
                    <li v-for="i in history.recentInvoices" :key="i.id" class="flex items-center justify-between border-b border-slate-100 py-1.5 dark:border-slate-800">
                        <Link :href="route('invoices.show', i.id)" class="text-brand-600 hover:text-brand-700">{{ i.invoice_number }}</Link>
                        <span>₹{{ i.total_amount }}</span>
                        <BaseBadge :color="invoiceStatusColor(i.status)">{{ i.status.replace('_', ' ') }}</BaseBadge>
                    </li>
                    <li v-if="history.recentInvoices.length === 0" class="text-slate-500">No bills yet.</li>
                </ul>
            </div>
        </div>
    </BaseCard>
</template>
