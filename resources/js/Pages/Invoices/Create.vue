<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { MinusIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseCombobox from '../../Components/Ui/BaseCombobox.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import { formatTime } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const discountTypeOptions = [
    { value: 'percentage', label: 'Percentage' },
    { value: 'flat', label: 'Flat amount' },
];

const props = defineProps({
    customers: { type: Array, required: true },
    employees: { type: Array, required: true },
    services: { type: Array, required: true },
    appointment: { type: Object, default: null },
});

const customerOptions = computed(() => props.customers.map((c) => ({ value: c.id, label: c.name, subtitle: c.phone })));
const employeeOptions = computed(() => props.employees.map((e) => ({ value: e.id, label: e.name })));

const isGuest = ref(false);

const form = useForm({
    customer_id: props.appointment?.customer?.id ?? '',
    guest_name: '',
    guest_phone: '',
    appointment_id: props.appointment?.id ?? null,
    items: [], // { service_id, name, employee_id, quantity, unit_price, gst_rate }
    discount_type: '',
    discount_value: 0,
    tip_amount: 0,
    notes: '',
});

if (props.appointment) {
    addService(props.appointment.service);
    form.items[0].employee_id = props.appointment.employee?.id ?? '';
}

function addService(service) {
    const existing = form.items.find((item) => item.service_id === service.id);
    if (existing) {
        existing.quantity++;
        return;
    }

    form.items.push({
        service_id: service.id,
        name: service.name,
        employee_id: '',
        quantity: 1,
        unit_price: Number(service.offer_price ?? service.price),
        gst_rate: Number(service.gst_rate),
    });
}

function removeItem(index) {
    form.items.splice(index, 1);
}

function changeQty(item, delta) {
    item.quantity = Math.max(1, item.quantity + delta);
}

const subtotal = computed(() => form.items.reduce((sum, item) => sum + item.unit_price * item.quantity, 0));

const discountAmount = computed(() => {
    if (!form.discount_type || !form.discount_value) return 0;
    const raw = form.discount_type === 'percentage' ? (subtotal.value * form.discount_value) / 100 : Number(form.discount_value);
    return Math.min(raw, subtotal.value);
});

const taxableAmount = computed(() => subtotal.value - discountAmount.value);

const taxAmount = computed(() => {
    if (subtotal.value === 0) return 0;
    return form.items.reduce((sum, item) => {
        const itemTaxable = (item.unit_price * item.quantity - (item.unit_price * item.quantity / subtotal.value) * discountAmount.value);
        return sum + itemTaxable * (item.gst_rate / 100);
    }, 0);
});

const total = computed(() => taxableAmount.value + taxAmount.value + Number(form.tip_amount || 0));

function submit() {
    form.transform((data) => ({
        ...data,
        items: data.items.map((item) => ({
            service_id: item.service_id,
            employee_id: item.employee_id || null,
            quantity: item.quantity,
        })),
    })).post(route('invoices.store'));
}
</script>

<template>
    <Head title="New Bill" />

    <h1 class="mb-1 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">New Bill</h1>
    <p v-if="appointment" class="mb-6 text-sm text-slate-500 dark:text-slate-400">
        Billing the {{ formatTime(appointment.starts_at) }} appointment for <span class="font-medium">{{ appointment.customer.name }}</span>
    </p>
    <div v-else class="mb-6" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <BaseCard title="Who's this bill for?">
                <div v-if="appointment" class="text-sm font-medium text-slate-900 dark:text-white">
                    {{ appointment.customer.name }}{{ appointment.customer.phone ? ` — ${appointment.customer.phone}` : '' }}
                </div>
                <template v-else>
                    <div class="mb-3 flex gap-2 text-sm">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5"
                            :class="!isGuest ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800'"
                            @click="isGuest = false"
                        >Existing customer</button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5"
                            :class="isGuest ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800'"
                            @click="isGuest = true; form.customer_id = ''"
                        >Guest / walk-in</button>
                    </div>

                    <BaseCombobox
                        v-if="!isGuest"
                        v-model="form.customer_id"
                        :options="customerOptions"
                        placeholder="Select a customer"
                        :error="form.errors.customer_id"
                    />
                    <div v-else class="grid grid-cols-2 gap-3">
                        <BaseInput v-model="form.guest_name" placeholder="Guest name" :error="form.errors.guest_name" />
                        <BaseInput v-model="form.guest_phone" placeholder="Phone (optional)" />
                    </div>
                </template>
            </BaseCard>

            <BaseCard title="Add services">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <button
                        v-for="service in services"
                        :key="service.id"
                        type="button"
                        class="rounded-xl border border-slate-200 p-3 text-left transition hover:border-brand-400 hover:bg-brand-50/50 dark:border-slate-700 dark:hover:bg-slate-800"
                        @click="addService(service)"
                    >
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ service.name }}</p>
                        <p class="text-xs text-slate-500">₹{{ service.offer_price ?? service.price }} · {{ service.duration_minutes }}m</p>
                    </button>
                </div>
            </BaseCard>

            <BaseCard title="Bill items">
                <p v-if="form.items.length === 0" class="text-sm text-slate-500">Tap a service above to add it to the bill.</p>
                <div v-for="(item, index) in form.items" :key="index" class="flex items-center justify-between border-b border-slate-100 py-3 last:border-0 dark:border-slate-800">
                    <div class="flex-1">
                        <p class="font-medium text-slate-900 dark:text-white">{{ item.name }}</p>
                        <BaseCombobox v-model="item.employee_id" class="mt-1 w-48" :options="employeeOptions" placeholder="No therapist" />
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-lg bg-slate-100 p-1.5 dark:bg-slate-800" @click="changeQty(item, -1)"><MinusIcon class="h-4 w-4" /></button>
                        <span class="w-6 text-center">{{ item.quantity }}</span>
                        <button type="button" class="rounded-lg bg-slate-100 p-1.5 dark:bg-slate-800" @click="changeQty(item, 1)"><PlusIcon class="h-4 w-4" /></button>
                    </div>
                    <p class="w-24 text-right font-medium">₹{{ (item.unit_price * item.quantity).toFixed(2) }}</p>
                    <button type="button" class="ml-3 text-rose-500 hover:text-rose-700" @click="removeItem(index)"><TrashIcon class="h-4 w-4" /></button>
                </div>
            </BaseCard>
        </div>

        <div class="space-y-6">
            <BaseCard title="Discount & tip">
                <div class="space-y-3">
                    <BaseListbox v-model="form.discount_type" label="Discount type" :options="discountTypeOptions" placeholder="No discount" />
                    <BaseInput v-if="form.discount_type" v-model="form.discount_value" type="number" label="Discount value" />
                    <BaseInput v-model="form.tip_amount" type="number" label="Tip (₹)" />
                </div>
            </BaseCard>

            <BaseCard title="Summary">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>₹{{ subtotal.toFixed(2) }}</dd></div>
                    <div v-if="discountAmount > 0" class="flex justify-between text-rose-600"><dt>Discount</dt><dd>-₹{{ discountAmount.toFixed(2) }}</dd></div>
                    <div class="flex justify-between"><dt>Tax (est.)</dt><dd>₹{{ taxAmount.toFixed(2) }}</dd></div>
                    <div v-if="form.tip_amount > 0" class="flex justify-between"><dt>Tip</dt><dd>₹{{ Number(form.tip_amount).toFixed(2) }}</dd></div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold dark:border-slate-700">
                        <dt>Total</dt><dd>₹{{ total.toFixed(2) }}</dd>
                    </div>
                </dl>
                <p class="mt-2 text-xs text-slate-400">Final tax split (CGST/SGST/IGST) is computed by the server.</p>

                <BaseButton class="mt-4 w-full" :disabled="form.processing || form.items.length === 0" @click="submit">
                    Create Bill
                </BaseButton>
            </BaseCard>
        </div>
    </div>
</template>
