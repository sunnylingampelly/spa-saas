<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseCombobox from '../../Components/Ui/BaseCombobox.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    appointment: { type: Object, required: true },
    employees: { type: Array, required: true },
});

const employeeOptions = computed(() => props.employees.map((e) => ({ value: e.id, label: e.name })));

const form = useForm({
    employee_id: props.appointment.employee_id ?? '',
    notes: props.appointment.notes ?? '',
});

const rescheduleForm = useForm({
    starts_at: props.appointment.starts_at.slice(0, 16),
});

function submit() {
    form.put(route('appointments.update', props.appointment.id));
}

function submitReschedule() {
    rescheduleForm.patch(route('appointments.reschedule', props.appointment.id));
}

function destroy() {
    if (confirm('Cancel and remove this appointment entirely?')) {
        router.delete(route('appointments.destroy', props.appointment.id));
    }
}
</script>

<template>
    <Head title="Edit Appointment" />

    <h1 class="mb-1 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">
        {{ appointment.customer.name }} — {{ appointment.service.name }}
    </h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">{{ formatDate(appointment.starts_at, { withTime: true }) }}</p>

    <div class="space-y-6">
        <form @submit.prevent="submit">
            <BaseCard title="Details">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseCombobox v-model="form.employee_id" label="Therapist" :options="employeeOptions" placeholder="Unassigned" />
                </div>
                <div class="mt-4">
                    <BaseTextarea v-model="form.notes" label="Notes" :rows="3" />
                </div>
                <BaseButton type="submit" class="mt-4" :disabled="form.processing">Save Changes</BaseButton>
            </BaseCard>
        </form>

        <form @submit.prevent="submitReschedule">
            <BaseCard title="Reschedule">
                <label class="form-label">New date & time</label>
                <input v-model="rescheduleForm.starts_at" type="datetime-local" class="form-input max-w-xs" required />
                <p v-if="rescheduleForm.errors.starts_at" class="mt-1.5 text-sm text-rose-600">{{ rescheduleForm.errors.starts_at }}</p>
                <BaseButton type="submit" variant="secondary" class="mt-4" :disabled="rescheduleForm.processing">Reschedule</BaseButton>
            </BaseCard>
        </form>

        <BaseButton variant="danger" @click="destroy">Delete Appointment</BaseButton>
    </div>
</template>
