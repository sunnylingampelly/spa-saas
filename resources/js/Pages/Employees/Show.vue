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

const leaveTypeOptions = [
    { value: 'casual', label: 'Casual' },
    { value: 'sick', label: 'Sick' },
    { value: 'paid', label: 'Paid' },
    { value: 'unpaid', label: 'Unpaid' },
    { value: 'other', label: 'Other' },
];

const props = defineProps({
    employee: { type: Object, required: true },
    attendanceSummary: { type: Object, required: true },
});

const statusColor = (status) => ({ active: 'green', inactive: 'slate', on_leave: 'amber' }[status] ?? 'slate');

const showLeaveForm = ref(false);
const leaveForm = useForm({
    leave_type: 'casual',
    start_date: '',
    end_date: '',
    reason: '',
});

function submitLeave() {
    leaveForm.post(route('employees.leaves.store', props.employee.id), {
        preserveScroll: true,
        onSuccess: () => { showLeaveForm.value = false; leaveForm.reset(); },
    });
}
</script>

<template>
    <Head :title="employee.name" />

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ employee.name }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ employee.employee_code }} · {{ employee.designation || '—' }} · {{ employee.department || '—' }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <BaseBadge :color="statusColor(employee.status)">{{ employee.status.replace('_', ' ') }}</BaseBadge>
            <Link :href="route('employees.edit', employee.id)"><BaseButton variant="secondary">Edit</BaseButton></Link>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <BaseCard title="Profile">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd>{{ employee.phone || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd>{{ employee.email || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Joined</dt><dd>{{ formatDate(employee.joining_date) || '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Experience</dt><dd>{{ employee.experience_years }} yrs</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Salary</dt><dd>₹{{ employee.salary ?? '—' }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Commission</dt>
                    <dd>{{ employee.commission_value }}{{ employee.commission_type === 'percentage' ? '%' : ' ₹ flat' }}</dd>
                </div>
                <div v-if="employee.performance_rating" class="flex justify-between">
                    <dt class="text-slate-500">Performance</dt><dd>{{ employee.performance_rating }} / 5</dd>
                </div>
            </dl>
        </BaseCard>

        <BaseCard title="Attendance (last 30 days)">
            <div class="grid grid-cols-2 gap-3">
                <div v-for="(count, status) in attendanceSummary" :key="status" class="rounded-xl bg-slate-50 px-3 py-2 text-center dark:bg-slate-800/60">
                    <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ count }}</p>
                    <p class="text-xs capitalize text-slate-500 dark:text-slate-400">{{ status.replace('_', ' ') }}</p>
                </div>
                <p v-if="Object.keys(attendanceSummary).length === 0" class="col-span-2 text-sm text-slate-500">No attendance marked yet.</p>
            </div>

            <ul class="mt-4 max-h-48 space-y-1 overflow-y-auto text-sm">
                <li v-for="a in employee.attendances" :key="a.id" class="flex justify-between border-b border-slate-100 py-1 dark:border-slate-800">
                    <span>{{ formatDate(a.attendance_date) }}</span>
                    <BaseBadge :color="a.status === 'present' ? 'green' : 'slate'">{{ a.status.replace('_', ' ') }}</BaseBadge>
                </li>
            </ul>
        </BaseCard>

        <BaseCard title="Leaves">
            <template #actions>
                <button class="text-sm font-medium text-brand-600 hover:text-brand-700" @click="showLeaveForm = !showLeaveForm">
                    + Record leave
                </button>
            </template>

            <form v-if="showLeaveForm" class="mb-4 space-y-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60" @submit.prevent="submitLeave">
                <BaseListbox v-model="leaveForm.leave_type" :options="leaveTypeOptions" />
                <div class="grid grid-cols-2 gap-2">
                    <BaseInput v-model="leaveForm.start_date" type="date" required />
                    <BaseInput v-model="leaveForm.end_date" type="date" required />
                </div>
                <BaseInput v-model="leaveForm.reason" type="text" placeholder="Reason (optional)" />
                <BaseButton type="submit" :disabled="leaveForm.processing">Save</BaseButton>
            </form>

            <ul class="max-h-48 space-y-1 overflow-y-auto text-sm">
                <li v-for="l in employee.leaves" :key="l.id" class="flex justify-between border-b border-slate-100 py-1 dark:border-slate-800">
                    <span>{{ formatDate(l.start_date) }} – {{ formatDate(l.end_date) }}</span>
                    <BaseBadge color="amber">{{ l.leave_type }}</BaseBadge>
                </li>
                <li v-if="employee.leaves.length === 0" class="text-slate-500">No leaves recorded.</li>
            </ul>
        </BaseCard>
    </div>
</template>
