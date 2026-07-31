<script setup>
import { Head, router } from '@inertiajs/vue3';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

defineProps({
    payments: { type: Array, required: true },
});

const rupees = (value) => `₹${Number(value).toLocaleString('en-IN')}`;

function confirm(payment) {
    router.post(route('admin.pending-payments.confirm', payment.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Pending Payments" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Pending Payments</h1>

    <BaseCard>
        <p v-if="!payments.length" class="text-sm text-slate-500 dark:text-slate-400">Nothing awaiting confirmation.</p>
        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                    <th class="pb-2 font-medium">Spa</th>
                    <th class="pb-2 font-medium">Plan</th>
                    <th class="pb-2 font-medium">Amount</th>
                    <th class="pb-2 font-medium">Note</th>
                    <th class="pb-2 font-medium">Submitted</th>
                    <th class="pb-2 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="payment in payments" :key="payment.id" class="border-b border-slate-50 dark:border-slate-800/60">
                    <td class="py-2 font-medium text-slate-900 dark:text-white">{{ payment.subscription?.spa?.name }}</td>
                    <td class="py-2 capitalize text-slate-600 dark:text-slate-300">{{ payment.plan_code }}</td>
                    <td class="py-2 text-slate-900 dark:text-white">{{ rupees(payment.amount) }}</td>
                    <td class="py-2 text-slate-500 dark:text-slate-400">{{ payment.proof_note || '—' }}</td>
                    <td class="py-2 text-slate-500 dark:text-slate-400">{{ formatDate(payment.created_at) }}</td>
                    <td class="py-2 text-right">
                        <BaseButton variant="secondary" class="!px-3 !py-1.5 text-xs" @click="confirm(payment)">Confirm</BaseButton>
                    </td>
                </tr>
            </tbody>
        </table>
    </BaseCard>
</template>
