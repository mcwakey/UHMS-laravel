import './bootstrap-inertia';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

const APP_NAME = import.meta.env.VITE_APP_NAME || 'UHMS';

createInertiaApp({
    title: (title) => (title ? `${title} · ${APP_NAME}` : APP_NAME),

    // Lazy-load each Vue page from resources/js/Pages/**.
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: false });
        const path = `./Pages/${name}.vue`;
        if (!pages[path]) {
            throw new Error(`[Inertia] Page not found: ${path}`);
        }
        return pages[path]();
    },

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },

    progress: {
        color: '#0d6efd',
        showSpinner: true,
    },
});
