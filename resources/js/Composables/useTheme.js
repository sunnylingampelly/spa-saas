import { storeToRefs } from 'pinia';
import { useUiStore } from '../Stores/ui';

export function useTheme() {
    const store = useUiStore();
    const { isDark } = storeToRefs(store);

    return {
        isDark,
        toggleTheme: () => store.toggleTheme(),
    };
}
