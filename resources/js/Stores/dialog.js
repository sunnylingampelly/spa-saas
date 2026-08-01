import { defineStore } from 'pinia';

// Backs the app-wide replacement for window.confirm()/prompt() — a single dialog instance,
// mounted once at the app root (see app.js), driven by whichever page last called
// confirmDialog()/promptDialog() via the useConfirm() composable.
export const useDialogStore = defineStore('dialog', {
    state: () => ({
        isOpen: false,
        mode: 'confirm', // 'confirm' | 'prompt'
        title: '',
        message: '',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        danger: false,
        inputValue: '',
        inputPlaceholder: '',
        inputRequired: false,
        resolve: null,
    }),

    actions: {
        open(options, resolve) {
            this.mode = options.mode;
            this.title = options.title ?? '';
            this.message = options.message ?? '';
            this.confirmLabel = options.confirmLabel ?? (options.mode === 'prompt' ? 'Submit' : 'Confirm');
            this.cancelLabel = options.cancelLabel ?? 'Cancel';
            this.danger = options.danger ?? false;
            this.inputValue = options.defaultValue ?? '';
            this.inputPlaceholder = options.placeholder ?? '';
            this.inputRequired = options.required ?? false;
            this.resolve = resolve;
            this.isOpen = true;
        },

        confirm() {
            const result = this.mode === 'prompt' ? this.inputValue : true;
            const resolve = this.resolve;
            this.close();
            resolve?.(result);
        },

        cancel() {
            const result = this.mode === 'prompt' ? null : false;
            const resolve = this.resolve;
            this.close();
            resolve?.(result);
        },

        close() {
            this.isOpen = false;
            this.resolve = null;
        },
    },
});
