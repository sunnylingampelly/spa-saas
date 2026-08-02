<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import ImportSpreadsheetModal from '../../Components/Ui/ImportSpreadsheetModal.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const attendanceStatusOptions = [
    { value: 'present', label: 'Present' },
    { value: 'absent', label: 'Absent' },
    { value: 'half_day', label: 'Half Day' },
    { value: 'on_leave', label: 'On Leave' },
    { value: 'holiday', label: 'Holiday' },
];

const props = defineProps({
    employees: { type: Object, required: true },
});

const statusColor = (status) => ({ active: 'green', inactive: 'slate', on_leave: 'amber' }[status] ?? 'slate');

function toggleStatus(employee, status) {
    router.patch(route('employees.toggle-status', employee.id), { status }, { preserveScroll: true });
}

// Bulk "mark today's attendance" panel
const showAttendance = ref(false);
const attendanceForm = useForm({
    attendance_date: new Date().toISOString().slice(0, 10),
    entries: props.employees.data.map((e) => ({ employee_id: e.id, status: 'present' })),
});

function submitAttendance() {
    attendanceForm.post(route('employees.attendance.store'), {
        preserveScroll: true,
        onSuccess: () => { showAttendance.value = false; },
    });
}
</script>

<template>
    <Head title="Employees" />

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Employees</h1>
        <div class="flex gap-3">
            <BaseButton variant="secondary" @click="showAttendance = !showAttendance">
                Mark Today's Attendance
            </BaseButton>
            <a :href="route('employees.export')">
                <BaseButton variant="secondary"><ArrowDownTrayIcon class="h-4 w-4" /> Export</BaseButton>
            </a>
            <ImportSpreadsheetModal
                label="employees"
                import-route="employees.import"
                import-template-route="employees.import-template"
            />
            <Link :href="route('employees.create')">
                <BaseButton><PlusIcon class="h-4 w-4" /> Add Employee</BaseButton>
            </Link>
        </div>
    </div>

    <BaseCard v-if="showAttendance" title="Mark attendance" class="mb-6">
        <form @submit.prevent="submitAttendance">
            <div class="mb-4 max-w-xs">
                <label class="form-label">Date</label>
                <input v-model="attendanceForm.attendance_date" type="date" class="form-input" />
            </div>

            <div class="space-y-2">
                <div
                    v-for="(entry, index) in attendanceForm.entries"
                    :key="entry.employee_id"
                    class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-2.5 dark:bg-slate-800/60"
                >
                    <span class="text-sm text-slate-700 dark:text-slate-300">
                        {{ employees.data[index].name }}
                    </span>
                    <BaseListbox v-model="entry.status" class="w-40" :options="attendanceStatusOptions" />
                </div>
            </div>

            <BaseButton type="submit" class="mt-4" :disabled="attendanceForm.processing">Save Attendance</BaseButton>
        </form>
    </BaseCard>

    <div v-if="employees.data.length === 0" class="card text-center text-sm text-slate-500 dark:text-slate-400">
        No employees yet.
    </div>

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="employee in employees.data" :key="employee.id" class="card !p-4">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-semibold text-white">
                    {{ employee.name.charAt(0) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-slate-900 dark:text-white">{{ employee.name }}</p>
                    <p class="truncate text-sm text-slate-500 dark:text-slate-400">{{ employee.designation || '—' }}</p>
                </div>
                <BaseBadge :color="statusColor(employee.status)">{{ employee.status.replace('_', ' ') }}</BaseBadge>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
                <span>{{ employee.department || 'No department' }}</span>
                <span>Joined {{ formatDate(employee.joining_date) }}</span>
            </div>

            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <Link :href="route('employees.show', employee.id)" class="font-medium text-brand-600 hover:text-brand-700">View</Link>
                    <Link :href="route('employees.edit', employee.id)" class="font-medium text-slate-500 hover:text-slate-700">Edit</Link>
                </div>
                <button
                    v-if="employee.status !== 'inactive'"
                    class="font-medium text-rose-600 hover:text-rose-700"
                    @click="toggleStatus(employee, 'inactive')"
                >
                    Deactivate
                </button>
                <button v-else class="font-medium text-emerald-600 hover:text-emerald-700" @click="toggleStatus(employee, 'active')">
                    Activate
                </button>
            </div>
        </div>
    </div>
</template>
