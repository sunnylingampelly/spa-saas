<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseCombobox from '../../Components/Ui/BaseCombobox.vue';
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

const props = defineProps({
    customer: { type: Object, required: true },
    services: { type: Array, required: true },
    employees: { type: Array, required: true },
});

const serviceOptions = computed(() => props.services.map((s) => ({ value: s.id, label: s.name })));
const employeeOptions = computed(() => props.employees.map((e) => ({ value: e.id, label: e.name })));

const form = useForm({
    name: props.customer.name,
    phone: props.customer.phone ?? '',
    whatsapp_number: props.customer.whatsapp_number ?? '',
    email: props.customer.email ?? '',
    gender: props.customer.gender ?? '',
    date_of_birth: props.customer.date_of_birth ?? '',
    anniversary_date: props.customer.anniversary_date ?? '',
    city: props.customer.city ?? '',
    state: props.customer.state ?? '',
    occupation: props.customer.occupation ?? '',
    preferred_service_id: props.customer.preferred_service_id ?? '',
    preferred_employee_id: props.customer.preferred_employee_id ?? '',
    medical_notes: props.customer.medical_notes ?? '',
    allergy_notes: props.customer.allergy_notes ?? '',
    is_vip: props.customer.is_vip,
});

function submit() {
    form.put(route('customers.update', props.customer.id));
}
</script>

<template>
    <Head title="Edit Customer" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Edit {{ customer.name }}</h1>

    <form class="space-y-6" @submit.prevent="submit">
        <BaseCard title="Basic details">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput v-model="form.name" label="Full name" required :error="form.errors.name" />
                <BaseListbox v-model="form.gender" label="Gender" :options="genderOptions" placeholder="Select" />
                <BaseInput v-model="form.phone" label="Phone" :error="form.errors.phone" />
                <BaseInput v-model="form.whatsapp_number" label="WhatsApp number" :error="form.errors.whatsapp_number" />
                <BaseInput v-model="form.email" type="email" label="Email" :error="form.errors.email" />
                <BaseInput v-model="form.occupation" label="Occupation" :error="form.errors.occupation" />
                <BaseInput v-model="form.date_of_birth" type="date" label="Date of birth" :error="form.errors.date_of_birth" />
                <BaseInput v-model="form.anniversary_date" type="date" label="Anniversary" :error="form.errors.anniversary_date" />
                <BaseInput v-model="form.city" label="City" :error="form.errors.city" />
                <BaseInput v-model="form.state" label="State" :error="form.errors.state" />
            </div>
        </BaseCard>

        <BaseCard title="Preferences">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseCombobox v-model="form.preferred_service_id" label="Preferred massage" :options="serviceOptions" placeholder="None" />
                <BaseCombobox v-model="form.preferred_employee_id" label="Preferred therapist" :options="employeeOptions" placeholder="None" />
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input v-model="form.is_vip" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                Mark as VIP customer
            </label>
        </BaseCard>

        <BaseCard title="Health notes">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseTextarea v-model="form.medical_notes" label="Medical notes" :rows="3" />
                <BaseTextarea v-model="form.allergy_notes" label="Allergy notes" :rows="3" />
            </div>
        </BaseCard>

        <BaseButton type="submit" :disabled="form.processing">Save Changes</BaseButton>
    </form>
</template>
