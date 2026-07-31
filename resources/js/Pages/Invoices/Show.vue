<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const paymentMethodOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'upi', label: 'UPI' },
    { value: 'card', label: 'Card' },
    { value: 'wallet', label: 'Wallet' },
    { value: 'gift_voucher', label: 'Gift voucher' },
    { value: 'bank_transfer', label: 'Bank transfer' },
];

const props = defineProps({
    invoice: { type: Object, required: true },
    payUrl: { type: String, default: null },
});

const statusColor = (status) => ({
    unpaid: 'rose',
    partially_paid: 'amber',
    paid: 'green',
    refunded: 'slate',
    cancelled: 'slate',
}[status] ?? 'slate');

const showPaymentForm = ref(false);
const paymentForm = useForm({ payments: [{ method: 'cash', amount: props.invoice.balance_amount, reference_number: '' }] });

function addPaymentRow() {
    paymentForm.payments.push({ method: 'cash', amount: 0, reference_number: '' });
}

function removePaymentRow(index) {
    paymentForm.payments.splice(index, 1);
}

function submitPayment() {
    paymentForm.post(route('invoices.payments.store', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => { showPaymentForm.value = false; },
    });
}

const showRefundForm = ref(false);
const refundForm = useForm({ method: 'cash', amount: '', reason: '' });

function submitRefund() {
    refundForm.post(route('invoices.refund', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => { showRefundForm.value = false; refundForm.reset(); },
    });
}

function cancelInvoice() {
    const reason = prompt('Reason for cancelling this invoice (optional):');
    if (reason !== null) {
        useForm({ reason }).post(route('invoices.cancel', props.invoice.id), { preserveScroll: true });
    }
}

function emailInvoice() {
    useForm({}).post(route('invoices.email', props.invoice.id), { preserveScroll: true });
}

const whatsappLink = computed(() => {
    const phone = props.invoice.customer?.whatsapp_number || props.invoice.customer?.phone || props.invoice.guest_phone;
    if (!phone) return null;
    const payLine = props.payUrl ? ` Pay online: ${props.payUrl}` : '';
    const message = encodeURIComponent(
        `Hi ${props.invoice.billedToName || ''}, here is your invoice ${props.invoice.invoice_number} for ₹${props.invoice.total_amount}. Download: ${route('invoices.download', props.invoice.id)}.${payLine}`
    );
    return `https://wa.me/${phone.replace(/\D/g, '')}?text=${message}`;
});

const linkCopied = ref(false);

async function copyPaymentLink() {
    await navigator.clipboard.writeText(props.payUrl);
    linkCopied.value = true;
    setTimeout(() => { linkCopied.value = false; }, 2000);
}

function printInvoice() {
    window.print();
}
</script>

<template>
    <Head :title="invoice.invoice_number" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <div>
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ invoice.invoice_number }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ formatDate(invoice.created_at, { withTime: true }) }} · FY {{ invoice.financial_year }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <BaseBadge :color="statusColor(invoice.status)">{{ invoice.status.replace('_', ' ') }}</BaseBadge>
            <BaseButton variant="secondary" @click="printInvoice">Print</BaseButton>
            <a :href="route('invoices.download', invoice.id)"><BaseButton variant="secondary">Download PDF</BaseButton></a>
            <BaseButton v-if="invoice.customer?.email" variant="secondary" @click="emailInvoice">Email</BaseButton>
            <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener"><BaseButton variant="secondary">WhatsApp</BaseButton></a>
            <BaseButton v-if="payUrl" variant="secondary" @click="copyPaymentLink">{{ linkCopied ? 'Copied!' : 'Copy Payment Link' }}</BaseButton>
            <BaseButton v-if="invoice.status === 'unpaid'" variant="danger" @click="cancelInvoice">Cancel</BaseButton>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <BaseCard title="Bill to">
                <p class="font-medium text-slate-900 dark:text-white">{{ invoice.customer?.name ?? invoice.guest_name ?? 'Guest' }}</p>
                <p class="text-sm text-slate-500">{{ invoice.customer?.phone ?? invoice.guest_phone ?? '' }}</p>
            </BaseCard>

            <BaseCard title="Items">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-slate-400">
                            <th class="pb-2">Service</th>
                            <th class="pb-2">Therapist</th>
                            <th class="pb-2 text-right">Qty</th>
                            <th class="pb-2 text-right">Rate</th>
                            <th class="pb-2 text-right">GST</th>
                            <th class="pb-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in invoice.items" :key="item.id" class="border-t border-slate-100 dark:border-slate-800">
                            <td class="py-2">{{ item.description }}</td>
                            <td class="py-2 text-slate-500">{{ item.employee?.name ?? '—' }}</td>
                            <td class="py-2 text-right">{{ item.quantity }}</td>
                            <td class="py-2 text-right">₹{{ item.unit_price }}</td>
                            <td class="py-2 text-right">{{ item.gst_rate }}%</td>
                            <td class="py-2 text-right">₹{{ item.line_total }}</td>
                        </tr>
                    </tbody>
                </table>
            </BaseCard>

            <BaseCard title="Payments" class="print:hidden">
                <template #actions>
                    <div class="flex gap-3 text-sm">
                        <button v-if="invoice.balance_amount > 0" class="font-medium text-brand-600 hover:text-brand-700" @click="showPaymentForm = !showPaymentForm">+ Record payment</button>
                        <button v-if="invoice.paid_amount > 0" class="font-medium text-rose-600 hover:text-rose-700" @click="showRefundForm = !showRefundForm">+ Refund</button>
                    </div>
                </template>

                <form v-if="showPaymentForm" class="mb-4 space-y-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60" @submit.prevent="submitPayment">
                    <div v-for="(payment, index) in paymentForm.payments" :key="index" class="flex items-center gap-2">
                        <BaseListbox v-model="payment.method" class="w-36" :options="paymentMethodOptions" />
                        <BaseInput v-model="payment.amount" type="number" placeholder="Amount" class="w-32" />
                        <BaseInput v-model="payment.reference_number" type="text" placeholder="Reference (optional)" />
                        <button v-if="paymentForm.payments.length > 1" type="button" class="text-rose-500" @click="removePaymentRow(index)">×</button>
                    </div>
                    <button type="button" class="text-sm text-brand-600 hover:text-brand-700" @click="addPaymentRow">+ Split into another payment method</button>
                    <p v-if="paymentForm.errors.amount" class="text-sm text-rose-600">{{ paymentForm.errors.amount }}</p>
                    <BaseButton type="submit" :disabled="paymentForm.processing">Save Payment</BaseButton>
                </form>

                <form v-if="showRefundForm" class="mb-4 space-y-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60" @submit.prevent="submitRefund">
                    <BaseListbox v-model="refundForm.method" label="Refund method" :options="paymentMethodOptions" />
                    <BaseInput v-model="refundForm.amount" type="number" label="Refund amount" :error="refundForm.errors.amount" />
                    <BaseInput v-model="refundForm.reason" type="text" label="Reason (optional)" />
                    <BaseButton type="submit" variant="danger" :disabled="refundForm.processing">Confirm Refund</BaseButton>
                </form>

                <ul class="space-y-1 text-sm">
                    <li v-for="p in invoice.payments" :key="p.id" class="flex justify-between border-b border-slate-100 py-1 dark:border-slate-800">
                        <span>{{ formatDate(p.paid_at, { withTime: true }) }} · {{ p.method.replace('_', ' ') }}</span>
                        <span :class="p.type === 'refund' ? 'text-rose-600' : 'text-emerald-600'">
                            {{ p.type === 'refund' ? '-' : '+' }}₹{{ p.amount }}
                        </span>
                    </li>
                    <li v-if="invoice.payments.length === 0" class="text-slate-500">No payments yet.</li>
                </ul>
            </BaseCard>
        </div>

        <BaseCard title="Summary">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>₹{{ invoice.subtotal }}</dd></div>
                <div v-if="invoice.discount_amount > 0" class="flex justify-between text-rose-600"><dt>Discount</dt><dd>-₹{{ invoice.discount_amount }}</dd></div>
                <div class="flex justify-between"><dt>Taxable amount</dt><dd>₹{{ invoice.taxable_amount }}</dd></div>
                <div v-if="invoice.cgst_amount > 0" class="flex justify-between"><dt>CGST</dt><dd>₹{{ invoice.cgst_amount }}</dd></div>
                <div v-if="invoice.sgst_amount > 0" class="flex justify-between"><dt>SGST</dt><dd>₹{{ invoice.sgst_amount }}</dd></div>
                <div v-if="invoice.igst_amount > 0" class="flex justify-between"><dt>IGST</dt><dd>₹{{ invoice.igst_amount }}</dd></div>
                <div v-if="invoice.tip_amount > 0" class="flex justify-between"><dt>Tip</dt><dd>₹{{ invoice.tip_amount }}</dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold dark:border-slate-700">
                    <dt>Total</dt><dd>₹{{ invoice.total_amount }}</dd>
                </div>
                <div class="flex justify-between text-emerald-600"><dt>Paid</dt><dd>₹{{ invoice.paid_amount }}</dd></div>
                <div class="flex justify-between font-medium"><dt>Balance due</dt><dd>₹{{ invoice.balance_amount }}</dd></div>
            </dl>
        </BaseCard>
    </div>
</template>

<style>
@media print {
    aside, header { display: none !important; }
}
</style>
