import { usePage } from '@inertiajs/vue3';

/**
 * Translation helper for Inertia pages.
 *
 * Reads the `i18n` shared prop (locale + lang groups, see
 * HandleInertiaRequests) and resolves dot-keys exactly like Laravel's
 * __() helper: t('pharmacy.dispensing_queue'), with :placeholder
 * replacement: t('pharmacy.items_count', { count: 3 }).
 * Unknown keys fall back to the key itself so untranslated strings
 * never break a page.
 */
export function useTrans() {
    const t = (key, replacements = {}) => {
        const page = usePage();
        let value = page.props.i18n?.translations ?? {};

        for (const part of key.split('.')) {
            if (value && typeof value === 'object' && part in value) {
                value = value[part];
            } else {
                value = null;
                break;
            }
        }

        if (typeof value !== 'string') {
            return key;
        }

        return value.replace(/:(\w+)/g, (match, name) =>
            name in replacements ? String(replacements[name]) : match,
        );
    };

    return { t };
}
