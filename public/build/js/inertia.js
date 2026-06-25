import './bootstrap-inertia';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import UhmsConfirmDialog from './Components/UhmsConfirmDialog.vue';
import { installGlobalConfirm, useConfirm } from './Composables/useConfirm';

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
        const targetWindow = window.self !== window.top ? window.top : window;
        targetWindow.location.href = url;
    } catch (_) {
        window.location.reload();
    }
}

function closeInertiaErrorDialog() {
    try {
        document.querySelectorAll('#inertia-error-dialog').forEach((dialog) => {
            if (typeof dialog.close === 'function') {
                dialog.close();
            }
            dialog.remove();
        });
    } catch (_) {}
}

function isAuthExpiredResponse(response) {
    if (!response) return false;
    if ([401, 419].includes(response.status)) return true;

    try {
        const responseUrl = new URL(response.request?.responseURL || '', window.location.href);
        return responseUrl.origin === window.location.origin && responseUrl.pathname.replace(/\/$/, '') === '/login';
    } catch (_) {
        return false;
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
    if (isAuthExpiredResponse(response)) {
        closeInertiaErrorDialog();
        fallbackToNative([401, 419].includes(response?.status) ? '/login' : url, 'auth-expired-inertia-response');
        return;
    }

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

// ─── Mount the global UhmsConfirmDialog ───────────────────────────────────
// Separate Vue app rooted at <div id="uhms-confirm-root">. Mounted once per
// page load so both Inertia pages AND legacy Blade pages (via the
// `window.UhmsConfirm` API) share the same dialog instance.
function mountGlobalConfirmDialog() {
    if (typeof document === 'undefined') return;
    if (document.getElementById('uhms-confirm-root')) return;
    const host = document.createElement('div');
    host.id = 'uhms-confirm-root';
    document.body.appendChild(host);
    createApp({ render: () => h(UhmsConfirmDialog) }).mount(host);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        mountGlobalConfirmDialog();
        installGlobalConfirm();
        installConfirmDelegates();
        upgradeLegacyConfirms(document);
    }, { once: true });
} else {
    mountGlobalConfirmDialog();
    installGlobalConfirm();
    installConfirmDelegates();
    upgradeLegacyConfirms(document);
}

// Re-run the legacy upgrader after every Inertia navigation in case a Blade
// page is injected via the legacy bridge.
router.on('finish', () => {
    queueMicrotask(() => {
        closeInertiaErrorDialog();
        document.documentElement.classList.remove('uhms-loading');
        upgradeLegacyConfirms(document);
    });
});

document.addEventListener('inertia:before', closeInertiaErrorDialog);
document.addEventListener('inertia:navigate', closeInertiaErrorDialog);
document.addEventListener('inertia:success', closeInertiaErrorDialog);

// ─── Delegated `data-confirm` handler for legacy Blade ────────────────────
// Lets us migrate `onsubmit="return confirm('Cancel this invoice?')"` to a
// drop-in `data-confirm="Cancel this invoice?"` attribute. The delegated
// handler intercepts submit / click, awaits the Vue dialog, then either
// resubmits or proceeds.
//
// Attribute reference (all optional except `data-confirm`):
//   data-confirm="Question shown as the message"
//   data-confirm-title="Heading (default: 'Confirm action')"
//   data-confirm-variant="danger|warning|info|success|primary"  (default 'primary')
//   data-confirm-label="Button text"      (default 'Confirm')
//   data-confirm-cancel-label="Cancel"    (default 'Cancel')
//   data-confirm-details="Extra hint shown smaller under the message"
function installConfirmDelegates() {
    const ATTR = 'data-confirm';
    const PROCEED = '__uhms_confirmed__';
    const { confirm } = useConfirm();

    function readOpts(el) {
        return {
            title: el.getAttribute('data-confirm-title') || 'Confirm action',
            message: el.getAttribute(ATTR) || 'Are you sure?',
            variant: el.getAttribute('data-confirm-variant') || 'primary',
            confirmLabel: el.getAttribute('data-confirm-label') || 'Confirm',
            cancelLabel: el.getAttribute('data-confirm-cancel-label') || 'Cancel',
            details: el.getAttribute('data-confirm-details') || undefined,
        };
    }

    // Forms: intercept submit.
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.hasAttribute(ATTR)) return;
        if (form[PROCEED]) {
            form[PROCEED] = false;
            return;
        }
        event.preventDefault();
        confirm(readOpts(form)).then((ok) => {
            if (!ok) return;
            form[PROCEED] = true;
            // Use requestSubmit() so HTML5 validation still runs; fall back
            // to submit() on older browsers.
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(event.submitter || undefined);
            } else {
                form.submit();
            }
        });
    }, true);

    // Buttons / links: intercept click for non-form-bound triggers.
    document.addEventListener('click', (event) => {
        const el = event.target?.closest?.(`[${ATTR}]`);
        if (!el) return;
        if (el instanceof HTMLFormElement) return; // handled above
        // If the element is inside a form that ALSO has data-confirm, let
        // the form handler take it (avoid double dialog).
        const parentForm = el.closest('form[data-confirm]');
        if (parentForm) return;
        if (el[PROCEED]) {
            el[PROCEED] = false;
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        confirm(readOpts(el)).then((ok) => {
            if (!ok) return;
            el[PROCEED] = true;
            // Re-fire the original action. For <a> just follow href, for
            // <button type="submit"> click again to submit, otherwise just
            // dispatch another click.
            if (el.tagName === 'A' && el.getAttribute('href')) {
                const href = el.getAttribute('href');
                if (window.UhmsInertia && href.startsWith('/')) {
                    window.UhmsInertia.visit(href);
                } else {
                    el.click();
                }
                return;
            }
            el.click();
        });
    }, true);
}

// ─── Legacy upgrader for `onsubmit="return confirm('...')"` patterns ──────
// Walks the DOM and rewrites any inline `confirm('msg')` handlers to the
// `data-confirm` attribute consumed by `installConfirmDelegates`. Native
// `confirm()` is then unreachable from those sites.
//
// Notes:
// - Only the simple form `return confirm('...')` (possibly followed by
//   `&& someFn(...)`) is auto-migrated. More complex inline handlers are
//   skipped — those sites must be migrated by hand or wrapped with the
//   `data-confirm` attribute explicitly.
// - Variant is inferred from class / button color when possible.
function upgradeLegacyConfirms(root) {
    const RE_SIMPLE = /^\s*return\s+confirm\(\s*(['"])([\s\S]*?)\1\s*\)\s*(?:&&\s*([\s\S]+?))?\s*;?\s*$/;

    function variantFromClassList(el) {
        const cl = (el.className || '') + '';
        if (/btn-(?:outline-)?danger/.test(cl)) return 'danger';
        if (/btn-(?:outline-)?warning/.test(cl)) return 'warning';
        if (/btn-(?:outline-)?success/.test(cl)) return 'success';
        if (/btn-(?:outline-)?info/.test(cl)) return 'info';
        return 'primary';
    }

    function attachConfirmAttr(target, message, variant) {
        if (target.hasAttribute('data-confirm')) return;
        // Decode any HTML entities that survived Blade rendering.
        const decoded = (() => {
            const t = document.createElement('textarea');
            t.innerHTML = message;
            return t.value;
        })();
        target.setAttribute('data-confirm', decoded);
        target.setAttribute('data-confirm-variant', variant);
        if (variant === 'danger') {
            target.setAttribute('data-confirm-label', 'Yes, continue');
        }
    }

    // Forms with `onsubmit="return confirm('...')"`.
    root.querySelectorAll('form[onsubmit]').forEach((form) => {
        const raw = form.getAttribute('onsubmit');
        if (!raw) return;
        const match = raw.match(RE_SIMPLE);
        if (!match) return;
        const message = match[2];
        const extraExpr = match[3]; // optional `&& saveTabBeforeSubmit(...)`
        const variant = /cancel|delete|remove|discharge|disable/i.test(message)
            ? 'danger'
            : variantFromClassList(form.querySelector('button[type="submit"]') || form);
        attachConfirmAttr(form, message, variant);
        // If there was a trailing `&& expr` we keep it as a new onsubmit
        // that only runs when the data-confirm proceed is set.
        if (extraExpr) {
            form.setAttribute('onsubmit', `return (${extraExpr});`);
        } else {
            form.removeAttribute('onsubmit');
        }
    });

    // Buttons / links with `onclick="return confirm('...')"`.
    root.querySelectorAll('[onclick]').forEach((el) => {
        const raw = el.getAttribute('onclick');
        if (!raw) return;
        const match = raw.match(RE_SIMPLE);
        if (!match) return;
        const message = match[2];
        const variant = /cancel|delete|remove|discharge|disable/i.test(message)
            ? 'danger'
            : variantFromClassList(el);
        attachConfirmAttr(el, message, variant);
        el.removeAttribute('onclick');
    });
}

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
