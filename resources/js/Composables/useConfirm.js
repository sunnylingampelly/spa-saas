import { useDialogStore } from '../Stores/dialog';

// Drop-in replacement for window.confirm()/window.prompt() using the app's own styled
// modal (ConfirmDialog.vue, mounted once globally in app.js) instead of a native browser
// dialog. Usage mirrors the native functions but is async:
//   if (await confirmDialog({ title: '...', message: '...', danger: true })) { ... }
//   const reason = await promptDialog({ title: '...', placeholder: '...' });
export function useConfirm() {
    const store = useDialogStore();

    function confirmDialog(options = {}) {
        return new Promise((resolve) => {
            store.open({ ...options, mode: 'confirm' }, resolve);
        });
    }

    function promptDialog(options = {}) {
        return new Promise((resolve) => {
            store.open({ ...options, mode: 'prompt' }, resolve);
        });
    }

    return { confirmDialog, promptDialog };
}
