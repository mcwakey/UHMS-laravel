import './bootstrap-inertia';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';

const APP_NAME = import.meta.env.VITE_APP_NAME || 'UHMS';

// ─── Bridge toast utility ─────────────────────────────────────────────────
// Surfaces visit errors / fallback events to the user instead of letting the
// page silently full-reload. Uses Bootstrap 5 toasts when available, plain DOM
// fallback otherwise.
function ensureToastContainer() {
    let container = document.getElementById('uhms-bridge-toasts');
    if (container) return container;
    container = document.createElement('div');
    container.id = 'uhms-bridge-toasts';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '11000';
    if (document.body) {
        document.body.appendChild(container);
    } else {
        document.addEventListener('DOMContentLoaded', () => document.body.appendChild(container), { once: true });
    }
    return container;
}

function showBridgeToast(message, variant = 'danger', delay = 6000) {
    try {
        const container = ensureToastContainer();
        const el = document.createElement('div');
        el.className = `toast align-items-center text-bg-${variant} border-0`;
        el.setAttribute('role', 'alert');
        el.setAttribute('aria-live', 'assertive');
        el.setAttribute('aria-atomic', 'true');
        el.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${String(message).replace(/[<>&"']/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;' }[c]))}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>`;
        container.appendChild(el);
        if (window.bootstrap?.Toast) {
            const t = new window.bootstrap.Toast(el, { delay });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            t.show();
        } else {
            el.style.display = 'block';
            setTimeout(() => el.remove(), delay);
        }
    } catch (e) {
        // eslint-disable-next-line no-console
        console.warn('[bridge] toast failed:', e, message);
    }
}

// Track recent failures per URL so a second failure within 5s falls back to
// native navigation (so the user is never stuck if the SPA path is broken).
const recentFailures = new Map();
function recordFailure(url) {
    const now = Date.now();
    const prior = recentFailures.get(url);
    recentFailures.set(url, now);
    // GC old entries (>30s)
    for (const [k, t] of recentFailures) {
        if (now - t > 30000) recentFailures.delete(k);
    }
    return prior && now - prior < 5000;
}

// Safety net: if any Inertia visit blows up internally (e.g. legacy responses
// missing a top-level `url` causing setPage -> hrefToUrl -> toString crash),
// fall back to a native browser navigation so the user is never stuck.
function fallbackToNative(targetUrl, reason) {
    // eslint-disable-next-line no-console
    console.warn('[bridge] fell back to native nav', { url: targetUrl, reason });
    try {
        const url = targetUrl || window.location.href;
        window.location.href = url;
    } catch (_) {
        window.location.reload();
    }
}

router.on('exception', (event) => {
    // Prevent Inertia from rethrowing.
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }
    // eslint-disable-next-line no-console
    console.warn('[bridge] visit exception', event?.detail?.exception);
    showBridgeToast('A page navigation failed. Please try again.', 'warning');
});

router.on('invalid', (event) => {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }
    const response = event?.detail?.response;
    const url = response?.request?.responseURL || window.location.href;
    const isRepeat = recordFailure(url);
    // eslint-disable-next-line no-console
    console.warn('[bridge] invalid Inertia response', { url, status: response?.status, isRepeat });
    if (isRepeat) {
        // Second failure in <5s → user is stuck; fall back to native nav.
        fallbackToNative(url, 'invalid-inertia-response-repeat');
    } else {
        showBridgeToast('The server returned an unexpected response. Refreshing the page if this repeats.', 'warning');
    }
});

window.addEventListener('unhandledrejection', (event) => {
    const message = String(event?.reason?.message || event?.reason || '');
    if (message.includes("access property \"toString\"") || message.includes("'toString'")) {
        event.preventDefault();
        // eslint-disable-next-line no-console
        console.warn('[bridge] swallowed toString crash from legacy visit');
    }
});

/**
 * Global Inertia helper for legacy Blade scripts (which cannot import from
 * @inertiajs/vue3 directly). Use these instead of `window.location.*` to keep
 * the page SPA-navigated.
 *
 *   window.UhmsInertia.visit('/some/url');
 *   window.UhmsInertia.reload({ preserveScroll: true });
 *   window.UhmsInertia.post('/some/url', { foo: 'bar' });
 *   window.UhmsInertia.toast('Saved!', 'success');
 */
window.UhmsInertia = {
    visit(url, opts = {}) {
        return router.visit(url, {
            preserveScroll: false,
            preserveState: false,
            ...opts,
        });
    },
    reload(opts = {}) {
        return router.reload({ preserveScroll: true, preserveState: true, ...opts });
    },
    post(url, data = {}, opts = {}) {
        return router.post(url, data, opts);
    },
    get(url, data = {}, opts = {}) {
        return router.get(url, data, opts);
    },
    toast(message, variant = 'info', delay = 5000) {
        showBridgeToast(message, variant, delay);
    },
};

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
