/**
 * Force-removes all Bootstrap modal state from the document.
 *
 * Call this before Inertia navigations (i.e. before the Vue v-html swap) to
 * prevent backdrop leaks, body scroll-lock, and orphaned Bootstrap Modal
 * instances that point at detached DOM nodes.
 *
 * Safe to call even when no modals are open — it is a no-op in that case.
 */
export function cleanupBootstrapModals() {
    document.querySelectorAll('.modal').forEach(function (modalEl) {
        try {
            var bsModal = window.bootstrap && window.bootstrap.Modal
                ? window.bootstrap.Modal.getInstance(modalEl)
                : null;

            if (bsModal) {
                bsModal.dispose();
            }
        } catch (e) {
            // Ignore — element may already be detached or Bootstrap threw
        }

        modalEl.classList.remove('show', 'fade');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.removeAttribute('aria-modal');
        modalEl.removeAttribute('role');
    });

    // Remove all backdrop elements left behind by Bootstrap
    document.querySelectorAll('.modal-backdrop').forEach(function (el) {
        el.remove();
    });

    // Restore body state managed by Bootstrap's ScrollBarHelper
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
}
