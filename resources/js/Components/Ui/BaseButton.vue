<script setup>
defineProps({
    variant: { type: String, default: 'primary' }, // primary | secondary | danger
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        :class="[
            variant === 'primary' && 'btn-primary',
            variant === 'secondary' && 'btn-secondary',
            variant === 'danger' && 'btn bg-rose-600 text-white shadow-sm hover:bg-rose-700 active:bg-rose-800',
        ]"
    >
        <slot />
    </button>
</template>
