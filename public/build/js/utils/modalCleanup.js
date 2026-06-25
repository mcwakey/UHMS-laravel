export function cleanupBootstrapModals() {
    if (typeof document === 'undefined') {
        return;
    }

    document.querySelectorAll('.modal').forEach((modal) => {
        try {
            const instance = window.bootstrap?.Modal?.getInstance(modal);
            if (instance) {
                instance.hide();
                instance.dispose();
            }
        } catch (_) {
            // Ignore stale Bootstrap instances left behind by legacy page swaps.
        }

        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
        modal.removeAttribute('role');
    });

    document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());

    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
}

export default cleanupBootstrapModals;
