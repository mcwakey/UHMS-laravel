import './bootstrap-inertia';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';

const APP_NAME = import.meta.env.VITE_APP_NAME || 'UHMS';

// Safety net: if any Inertia visit blows up internally (e.g. legacy responses
// missing a top-level `url` causing setPage -> hrefToUrl -> toString crash),
// fall back to a native browser navigation so the user is never stuck.
function fallbackToNative(targetUrl) {
    try {
        const url = targetUrl || window.location.href;
        window.location.href = url;
    } catch (_) {
        window.location.reload();
    }
}

router.on('exception', (event) => {
    // Prevent Inertia from rethrowing; reload the current URL natively.
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }
    // eslint-disable-next-line no-console
    console.warn('[Inertia] visit exception, falling back to native navigation', event?.detail?.exception);
});

router.on('invalid', (event) => {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }
    const response = event?.detail?.response;
    fallbackToNative(response?.request?.responseURL);
});

window.addEventListener('unhandledrejection', (event) => {
    const message = String(event?.reason?.message || event?.reason || '');
    if (message.includes("access property \"toString\"") || message.includes("'toString'")) {
        event.preventDefault();
        // eslint-disable-next-line no-console
        console.warn('[Inertia] swallowed toString crash from legacy visit');
    }
});

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
