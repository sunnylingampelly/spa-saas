<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseCombobox from '../../Components/Ui/BaseCombobox.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import BaseModal from '../../Components/Ui/BaseModal.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const bookingTypeOptions = [
    { value: 'advance', label: 'Advance booking' },
    { value: 'walk_in', label: 'Walk-in' },
];

const leadSourceOptions = [
    { value: 'walk_in', label: 'Walk-in' },
    { value: 'google_ads', label: 'Google Ads' },
    { value: 'meta_ads', label: 'Meta Ads (Facebook/Instagram)' },
    { value: 'referral', label: 'Referral' },
    { value: 'website', label: 'Website / Organic' },
    { value: 'phone_enquiry', label: 'Phone enquiry' },
    { value: 'other', label: 'Other' },
];

const props = defineProps({
    customers: { type: Array, required: true },
    employees: { type: Array, required: true },
    services: { type: Array, required: true },
    initialDate: { type: String, required: true },
});

// Local, mutable copy — a customer created inline via the modal below is pushed in here
// and selected immediately, without a full page reload.
const customerList = ref([...props.customers]);
const customerOptions = computed(() => customerList.value.map((c) => ({ value: c.id, label: c.name, subtitle: c.phone })));
const serviceOptions = computed(() => props.services.map((s) => ({
    value: s.id,
    label: s.name,
    subtitle: `${s.duration_minutes}m · ₹${s.price}`,
})));
const employeeOptions = computed(() => props.employees.map((e) => ({ value: e.id, label: e.name })));

const form = useForm({
    customer_id: '',
    employee_id: '',
    service_id: '',
    booking_type: 'advance',
    lead_source: 'walk_in',
    starts_at: `${props.initialDate}T10:00`,
    notes: '',
});

function submit() {
    form.post(route('appointments.store'));
}

// Quick-add customer, triggered from the combobox's "+ Add … as a new customer" row.
const showQuickAdd = ref(false);
const quickAddForm = useForm({ name: '', phone: '' });

function openQuickAdd(query) {
    quickAddForm.name = query;
    quickAddForm.phone = '';
    quickAddForm.clearErrors();
    showQuickAdd.value = true;
}

async function submitQuickAdd() {
    quickAddForm.processing = true;
    quickAddForm.clearErrors();

    try {
        const { data: customer } = await axios.post(route('customers.quick-create'), {
            name: quickAddForm.name,
            phone: quickAddForm.phone || null,
        });

        customerList.value.push(customer);
        form.customer_id = customer.id;
        showQuickAdd.value = false;
    } catch (err) {
        if (err.response?.status === 422) {
            const errors = err.response.data.errors ?? {};
            quickAddForm.setError(
                Object.fromEntries(Object.entries(errors).map(([field, messages]) => [field, messages[0]])),
            );
        }
    } finally {
        quickAddForm.processing = false;
    }
}
</script>

<template>
    <Head title="Book Appointment" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Book Appointment</h1>

    <form class="space-y-6" @submit.prevent="submit">
        <BaseCard title="Booking details">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseCombobox
                    v-model="form.customer_id"
                    label="Customer"
                    required
                    :options="customerOptions"
                    placeholder="Search by name or phone…"
                    create-label="Add &quot;{query}&quot; as a new customer"
                    :error="form.errors.customer_id"
                    @create="openQuickAdd"
                />

                <BaseListbox v-model="form.booking_type" label="Booking type" :options="bookingTypeOptions" />

                <BaseListbox
                    v-model="form.lead_source"
                    label="Booking source"
                    :options="leadSourceOptions"
                    :error="form.errors.lead_source"
                />

                <BaseCombobox
                    v-model="form.service_id"
                    label="Service"
                    required
                    :options="serviceOptions"
                    placeholder="Select a service"
                    :error="form.errors.service_id"
                />

                <BaseCombobox
                    v-model="form.employee_id"
                    label="Therapist (optional)"
                    :options="employeeOptions"
                    placeholder="Unassigned"
                />

                <div class="sm:col-span-2">
                    <label class="form-label">Date & time</label>
                    <input v-model="form.starts_at" type="datetime-local" class="form-input" required />
                    <p v-if="form.errors.starts_at || form.errors.employee_id" class="mt-1.5 text-sm text-rose-600">
                        {{ form.errors.starts_at || form.errors.employee_id }}
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <BaseTextarea v-model="form.notes" label="Notes" :rows="3" />
            </div>
        </BaseCard>

        <BaseButton type="submit" :disabled="form.processing">Book Appointment</BaseButton>
    </form>

    <BaseModal :show="showQuickAdd" title="Add a new customer" @close="showQuickAdd = false">
        <form class="space-y-4" @submit.prevent="submitQuickAdd">
            <BaseInput v-model="quickAddForm.name" label="Full name" required autofocus :error="quickAddForm.errors.name" />
            <BaseInput v-model="quickAddForm.phone" label="Phone (optional)" :error="quickAddForm.errors.phone" />
            <p class="text-xs text-slate-400">You can fill in the rest of their profile later from the Customers page.</p>
            <div class="flex justify-end gap-3">
                <BaseButton type="button" variant="secondary" @click="showQuickAdd = false">Cancel</BaseButton>
                <BaseButton type="submit" :disabled="quickAddForm.processing">Add customer</BaseButton>
            </div>
        </form>
    </BaseModal>
</template>
