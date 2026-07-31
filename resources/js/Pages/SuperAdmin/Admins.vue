<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

defineProps({
    admins: { type: Array, required: true },
});

const showForm = ref(false);
const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

function submit() {
    form.post(route('admin.admins.store'), {
        preserveScroll: true,
        onSuccess: () => { showForm.value = false; form.reset(); },
    });
}
</script>

<template>
    <Head title="Admins" />

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Admins</h1>
        <BaseButton @click="showForm = !showForm">+ Add Admin</BaseButton>
    </div>

    <BaseCard v-if="showForm" title="New admin account" class="mb-6">
        <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="submit">
            <BaseInput v-model="form.name" label="Full name" required :error="form.errors.name" />
            <BaseInput v-model="form.email" type="email" label="Email" required :error="form.errors.email" />
            <BaseInput v-model="form.password" type="password" label="Password" required :error="form.errors.password" />
            <BaseInput v-model="form.password_confirmation" type="password" label="Confirm password" required />
            <BaseButton type="submit" class="sm:col-span-2" :disabled="form.processing">Create Admin</BaseButton>
        </form>
    </BaseCard>

    <BaseCard>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                    <th class="pb-2 font-medium">Name</th>
                    <th class="pb-2 font-medium">Email</th>
                    <th class="pb-2 font-medium">Last login</th>
                    <th class="pb-2 font-medium">Created</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="admin in admins" :key="admin.id" class="border-b border-slate-50 dark:border-slate-800/60">
                    <td class="py-2 font-medium text-slate-900 dark:text-white">{{ admin.name }}</td>
                    <td class="py-2 text-slate-600 dark:text-slate-300">{{ admin.email }}</td>
                    <td class="py-2 text-slate-500 dark:text-slate-400">{{ admin.last_login_at ? formatDate(admin.last_login_at, { withTime: true }) : 'Never' }}</td>
                    <td class="py-2 text-slate-500 dark:text-slate-400">{{ formatDate(admin.created_at) }}</td>
                </tr>
            </tbody>
        </table>
    </BaseCard>
</template>
