import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import ConfirmDialog from './Components/ConfirmDialog.vue';
import { useUiStore } from './Stores/ui';

const appName = import.meta.env.VITE_APP_NAME || 'SpaOrbit';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const app = createApp({ render: () => [h(App, props), h(ConfirmDialog)] })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue);

        app.mount(el);

        useUiStore().init();
    },
    progress: {
        color: '#6366f1',
    },
});
