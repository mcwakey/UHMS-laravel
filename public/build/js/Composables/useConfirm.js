const CONFIRM_EVENT = 'uhms:confirm';

export function useConfirm() {
    return {
        confirm(options = {}) {
            return new Promise((resolve) => {
                window.dispatchEvent(new CustomEvent(CONFIRM_EVENT, {
                    detail: {
                        options,
                        resolve,
                    },
                }));
            });
        },
    };
}

export function installGlobalConfirm() {
    if (typeof window === 'undefined' || window.UhmsConfirm) return;

    window.UhmsConfirm = {
        confirm(options = {}) {
            return useConfirm().confirm(options);
        },
    };
}

export { CONFIRM_EVENT };
