<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseTable from '../../Components/Ui/BaseTable.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    expenses: { type: Object, required: true },
    categories: { type: Array, required: true },
});

const columns = [
    { key: 'category', label: 'Category' },
    { key: 'amount', label: 'Amount' },
    { key: 'expense_date', label: 'Date' },
    { key: 'notes', label: 'Notes' },
    { key: 'actions', label: '' },
];

function destroy(expense) {
    if (confirm(`Delete this ${expense.category} expense?`)) {
        router.delete(route('expenses.destroy', expense.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Expenses" />

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Expenses</h1>
        <Link :href="route('expenses.create')">
            <BaseButton><PlusIcon class="h-4 w-4" /> Add Expense</BaseButton>
        </Link>
    </div>

    <BaseCard>
        <BaseTable :columns="columns" :rows="expenses.data" empty-message="No expenses recorded yet.">
            <template #cell-category="{ row }"><BaseBadge color="slate">{{ row.category }}</BaseBadge></template>
            <template #cell-amount="{ row }">₹{{ row.amount }}</template>
            <template #cell-expense_date="{ row }">{{ formatDate(row.expense_date) }}</template>
            <template #cell-notes="{ row }">{{ row.notes || '—' }}</template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-3">
                    <Link :href="route('expenses.edit', row.id)" class="text-slate-500 hover:text-slate-700">Edit</Link>
                    <button class="text-rose-600 hover:text-rose-700" @click="destroy(row)">Delete</button>
                </div>
            </template>
        </BaseTable>
    </BaseCard>
</template>
