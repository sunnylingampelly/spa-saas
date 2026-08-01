<script setup>
import { storeToRefs } from 'pinia';
import { useDialogStore } from '../Stores/dialog';
import BaseButton from './Ui/BaseButton.vue';
import BaseModal from './Ui/BaseModal.vue';
import BaseTextarea from './Ui/BaseTextarea.vue';

const store = useDialogStore();
const { isOpen, mode, title, message, confirmLabel, cancelLabel, danger, inputValue, inputPlaceholder, inputRequired } = storeToRefs(store);

function onConfirm() {
    if (mode.value === 'prompt' && inputRequired.value && !inputValue.value.trim()) return;
    store.confirm();
}
</script>

<template>
    <BaseModal :show="isOpen" :title="title" @close="store.cancel()">
        <p v-if="message" class="whitespace-pre-wrap text-sm text-slate-600 dark:text-slate-300">{{ message }}</p>

        <BaseTextarea v-if="mode === 'prompt'" v-model="inputValue" :placeholder="inputPlaceholder" :rows="3" class="mt-4" />

        <div class="mt-6 flex justify-end gap-3">
            <BaseButton variant="secondary" @click="store.cancel()">{{ cancelLabel }}</BaseButton>
            <BaseButton :variant="danger ? 'danger' : 'primary'" @click="onConfirm">{{ confirmLabel }}</BaseButton>
        </div>
    </BaseModal>
</template>
