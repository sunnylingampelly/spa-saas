<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = useForm({
    name: '',
    phone: '',
    email: '',
    gst_number: '',
    city: '',
    state: '',
});

function submit() {
    form.post('/onboarding/create-spa');
}
</script>

<template>
    <Head title="Set up your spa" />

    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Tell us about your spa</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">You can fill in the rest of the details later from Settings.</p>

    <form class="space-y-5" @submit.prevent="submit">
        <BaseInput v-model="form.name" label="Spa name" autofocus required :error="form.errors.name" />

        <div class="grid grid-cols-2 gap-4">
            <BaseInput v-model="form.phone" label="Phone" required :error="form.errors.phone" />
            <BaseInput v-model="form.email" type="email" label="Email (optional)" :error="form.errors.email" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <BaseInput v-model="form.city" label="City (optional)" :error="form.errors.city" />
            <BaseInput v-model="form.state" label="State" required :error="form.errors.state" />
        </div>
        <p class="-mt-3 text-xs text-slate-400">State determines whether GST is split as CGST/SGST or charged as IGST on your invoices.</p>

        <BaseInput v-model="form.gst_number" label="GST number (optional)" :error="form.errors.gst_number" />

        <BaseButton type="submit" class="w-full" :disabled="form.processing">Create my spa</BaseButton>
    </form>
</template>
