<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    spa: { type: Object, required: true },
});

const form = useForm({
    name: props.spa.name,
    legal_business_name: props.spa.legal_business_name,
    gst_number: props.spa.gst_number,
    pan_number: props.spa.pan_number,
    phone: props.spa.phone,
    email: props.spa.email,
    address_line_1: props.spa.address_line_1,
    address_line_2: props.spa.address_line_2,
    city: props.spa.city,
    state: props.spa.state,
    pincode: props.spa.pincode,
    google_maps_link: props.spa.google_maps_link,
    opening_time: props.spa.opening_time,
    closing_time: props.spa.closing_time,
    invoice_prefix: props.spa.invoice_prefix,
    invoice_footer_note: props.spa.invoice_footer_note,
});

function submit() {
    form.put('/spa/profile');
}
</script>

<template>
    <Head title="Spa Profile" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Spa Profile</h1>

    <form class="space-y-6" @submit.prevent="submit">
        <BaseCard title="Business details">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput v-model="form.name" label="Spa name" required :error="form.errors.name" />
                <BaseInput v-model="form.legal_business_name" label="Legal business name" :error="form.errors.legal_business_name" />
                <BaseInput v-model="form.gst_number" label="GST number" :error="form.errors.gst_number" />
                <BaseInput v-model="form.pan_number" label="PAN number" :error="form.errors.pan_number" />
            </div>
        </BaseCard>

        <BaseCard title="Contact & location">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput v-model="form.phone" label="Phone" :error="form.errors.phone" />
                <BaseInput v-model="form.email" type="email" label="Email" :error="form.errors.email" />
                <BaseInput v-model="form.address_line_1" label="Address line 1" :error="form.errors.address_line_1" />
                <BaseInput v-model="form.address_line_2" label="Address line 2" :error="form.errors.address_line_2" />
                <BaseInput v-model="form.city" label="City" :error="form.errors.city" />
                <BaseInput v-model="form.state" label="State" :error="form.errors.state" />
                <BaseInput v-model="form.pincode" label="Pincode" :error="form.errors.pincode" />
                <BaseInput v-model="form.google_maps_link" label="Google Maps link" :error="form.errors.google_maps_link" />
            </div>
        </BaseCard>

        <BaseCard title="Hours & invoicing">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput v-model="form.opening_time" type="time" label="Opening time" :error="form.errors.opening_time" />
                <BaseInput v-model="form.closing_time" type="time" label="Closing time" :error="form.errors.closing_time" />
                <BaseInput v-model="form.invoice_prefix" label="Invoice prefix" :error="form.errors.invoice_prefix" />
                <BaseInput v-model="form.invoice_footer_note" label="Invoice footer note" :error="form.errors.invoice_footer_note" />
            </div>
        </BaseCard>

        <BaseButton type="submit" :disabled="form.processing">Save changes</BaseButton>
    </form>
</template>
