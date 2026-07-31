<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import BaseButton from '../Components/Ui/BaseButton.vue';
import GuestLayout from '../Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });
</script>

<template>
    <Head title="Account suspended" />

    <div class="text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300">
            <ExclamationTriangleIcon class="h-6 w-6" />
        </div>
        <h1 class="mb-2 text-xl font-semibold text-slate-900 dark:text-white">Your account has been suspended</h1>
        <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">
            Access to your spa's dashboard has been paused by the platform. If you believe this is a mistake,
            please contact support to resolve it.
        </p>
        <BaseButton variant="secondary" class="w-full" @click="router.post(route('logout'))">Log out</BaseButton>
    </div>
</template>
