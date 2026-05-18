import { reactive } from 'vue';

/**
 * UHMS confirmation dialog state — singleton.
 *
 * The actual UI is rendered by `Components/UhmsConfirmDialog.vue`, mounted
 * once at the AppLayout root. This module exposes:
 *
 *  - `uhmsConfirmState` — reactive state read by the dialog component.
 *  - `resolveConfirm`   — internal resolver invoked by the dialog buttons.
 *  - `useConfirm`       — composable returning `{ confirm, alert }` promises.
 *
 * Both `confirm` and `alert` return a Promise<boolean>. `alert` resolves
 * `true` once the user dismisses it.
 *
 * Native `window.confirm` / `window.alert` MUST NOT be used in new code.
 */

export const uhmsConfirmState = reactive({
    open: false,
    options: null,
});

let pendingResolver = null;

export function resolveConfirm(value) {
    const resolver = pendingResolver;
    pendingResolver = null;
    uhmsConfirmState.open = false;
    uhmsConfirmState.options = null;
    if (resolver) resolver(Boolean(value));
}

function openDialog(options) {
    // If a previous promise is still pending (rare), resolve it as cancelled
    // so we never leak unresolved promises.
    if (pendingResolver) {
        const stale = pendingResolver;
        pendingResolver = null;
        try { stale(false); } catch (_) { /* noop */ }
    }
    uhmsConfirmState.options = options;
    uhmsConfirmState.open = true;
    return new Promise((resolve) => {
        pendingResolver = resolve;
    });
}

export function useConfirm() {
    function confirm(opts = {}) {
        return openDialog({
            title: 'Confirm action',
            message: 'Are you sure you want to proceed?',
            confirmLabel: 'Confirm',
            cancelLabel: 'Cancel',
            variant: 'primary',
            ...(typeof opts === 'string' ? { message: opts } : opts),
        });
    }

    function alert(opts = {}) {
        return openDialog({
            title: 'Notice',
            message: '',
            confirmLabel: 'OK',
            variant: 'info',
            alertOnly: true,
            ...(typeof opts === 'string' ? { message: opts } : opts),
        });
    }

    return { confirm, alert };
}

/**
 * Expose a vanilla JS API on `window.UhmsConfirm` so legacy Blade scripts
 * can invoke the same dialog. Returns a Promise<boolean>.
 *
 *   window.UhmsConfirm.show({ message: 'Delete?' }).then(ok => { if (ok) ... });
 *
 * Initialized by `inertia.js` once the bridge boots.
 */
export function installGlobalConfirm() {
    if (typeof window === 'undefined') return;
    const api = useConfirm();
    window.UhmsConfirm = {
        show: (opts) => api.confirm(opts),
        alert: (opts) => api.alert(opts),
    };
}
