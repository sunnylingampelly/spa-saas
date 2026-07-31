<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const genderOptions = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
    { value: 'other', label: 'Other' },
];

const commissionTypeOptions = [
    { value: 'percentage', label: 'Percentage' },
    { value: 'flat', label: 'Flat amount' },
];

const form = useForm({
    name: '',
    gender: '',
    phone: '',
    email: '',
    department: '',
    designation: '',
    joining_date: '',
    salary: '',
    commission_type: 'percentage',
    commission_value: 0,
    experience_years: 0,
    notes: '',
});

function submit() {
    form.post(route('employees.store'));
}
</script>

<template>
    <Head title="Add Employee" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Add Employee</h1>

    <form class="space-y-6" @submit.prevent="submit">
        <BaseCard title="Basic details">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput v-model="form.name" label="Full name" required autofocus :error="form.errors.name" />
                <BaseListbox v-model="form.gender" label="Gender" :options="genderOptions" placeholder="Select" />
                <BaseInput v-model="form.phone" label="Phone" :error="form.errors.phone" />
                <BaseInput v-model="form.email" type="email" label="Email" :error="form.errors.email" />
            </div>
        </BaseCard>

        <BaseCard title="Employment">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput v-model="form.department" label="Department" :error="form.errors.department" />
                <BaseInput v-model="form.designation" label="Designation" :error="form.errors.designation" />
                <BaseInput v-model="form.joining_date" type="date" label="Joining date" :error="form.errors.joining_date" />
                <BaseInput v-model="form.experience_years" type="number" label="Experience (years)" :error="form.errors.experience_years" />
            </div>
        </BaseCard>

        <BaseCard title="Salary & commission">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <BaseInput v-model="form.salary" type="number" label="Monthly salary (₹)" :error="form.errors.salary" />
                <BaseListbox v-model="form.commission_type" label="Commission type" :options="commissionTypeOptions" />
                <BaseInput v-model="form.commission_value" type="number" label="Commission value" :error="form.errors.commission_value" />
            </div>
        </BaseCard>

        <BaseCard title="Notes">
            <BaseTextarea v-model="form.notes" :rows="3" placeholder="Anything worth noting about this employee" />
        </BaseCard>

        <BaseButton type="submit" :disabled="form.processing">Save Employee</BaseButton>
    </form>
</template>
