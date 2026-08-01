<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const form = useForm({ subject: '', body: '' });

function submit() {
    form.post(route('support.tickets.store'));
}
</script>

<template>
    <Head title="New Support Ticket" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">New Support Ticket</h1>

    <BaseCard class="mx-auto max-w-2xl">
        <form class="space-y-4" @submit.prevent="submit">
            <BaseInput v-model="form.subject" label="Subject" required autofocus placeholder="e.g. Can't record a UPI payment" :error="form.errors.subject" />
            <BaseTextarea
                v-model="form.body"
                label="Describe the issue"
                :rows="6"
                placeholder="What were you trying to do, and what happened instead?"
                :error="form.errors.body"
            />
            <BaseButton type="submit" :disabled="form.processing">Submit Ticket</BaseButton>
        </form>
    </BaseCard>
</template>
