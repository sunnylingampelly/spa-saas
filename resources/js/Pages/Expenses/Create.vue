<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    categories: { type: Array, required: true },
});

const form = useForm({
    category: '',
    amount: '',
    expense_date: new Date().toISOString().slice(0, 10),
    notes: '',
});

function submit() {
    form.post(route('expenses.store'));
}
</script>

<template>
    <Head title="Add Expense" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Add Expense</h1>

    <form class="max-w-lg space-y-6" @submit.prevent="submit">
        <BaseCard>
            <div class="space-y-4">
                <BaseListbox
                    v-model="form.category"
                    label="Category"
                    required
                    :options="categories"
                    placeholder="Select a category"
                    :error="form.errors.category"
                />
                <BaseInput v-model="form.amount" type="number" label="Amount (₹)" required :error="form.errors.amount" />
                <BaseInput v-model="form.expense_date" type="date" label="Date" required :error="form.errors.expense_date" />
                <BaseTextarea v-model="form.notes" label="Notes (optional)" :rows="3" />
            </div>
        </BaseCard>

        <BaseButton type="submit" :disabled="form.processing">Save Expense</BaseButton>
    </form>
</template>
