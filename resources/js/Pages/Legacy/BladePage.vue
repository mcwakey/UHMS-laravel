<template>
    <Head :title="title" />
    <div
        ref="legacyRoot"
        class="uhms-inertia-legacy-page"
        v-html="html"
        @click="handleClick"
        @submit="handleSubmit"
    />
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { cleanupBootstrapModals } from '../../utils/modalCleanup';

const props = defineProps({
    html: {
        type: String,
        required: true,
    },
    scripts: {
        type: String,
        default: '',
    },
    styles: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: 'UHMS',
    },
    url: {
        type: String,
        default: '',
    },
});

let scriptRunId = 0;
let styleRunId = 0;
const legacyRoot = ref(null);

function isPlainLeftClick(event) {
    return (
        event.button === 0 &&
        !event.metaKey &&
        !event.ctrlKey &&
        !event.shiftKey &&
        !event.altKey
    );
}

function isSameOrigin(url) {
    try {
        const u = new URL(url, window.location.href);
        return u.origin === window.location.origin;
    } catch (_) {
        return false;
    }
}

function showToastFallback(message, variant = 'warning') {
    if (window.UhmsInertia && typeof window.UhmsInertia.toast === 'function') {
        window.UhmsInertia.toast(message, variant);
    }
}

// Heuristic: actions that almost certainly return a binary download (PDF/CSV/
// XLSX/print) should NOT be intercepted by Inertia — visit() would choke on
// the non-JSON body and we'd fall back to native anyway. Faster + cleaner to
// skip up-front.
const DOWNLOAD_ACTION_RE = /(?:\.pdf|\.csv|\.xlsx?|\.docx?|\/(?:export|download|print|pdf)(?:[\/?#]|$))/i;
function actionLooksLikeDownload(action) {
    if (!action) return false;
    try {
        const u = new URL(action, window.location.href);
        return DOWNLOAD_ACTION_RE.test(u.pathname + u.search);
    } catch (_) {
        return DOWNLOAD_ACTION_RE.test(action);
    }
}

function shouldIgnoreAnchor(anchor) {
    if (!anchor || !anchor.getAttribute) return true;
    const href = anchor.getAttribute('href');
    if (!href) return true;
    if (href.startsWith('#')) return true;
    if (/^(mailto:|tel:|javascript:|data:|blob:)/i.test(href)) return true;
    if (anchor.hasAttribute('download')) return true;
    if (anchor.hasAttribute('data-no-inertia')) return true;
    if (anchor.target && anchor.target !== '' && anchor.target !== '_self') return true;
    if (anchor.getAttribute('role') === 'button' && !href.replace('#', '')) return true;
    // Skip Bootstrap / jQuery toggle anchors that shouldn't navigate.
    const toggle = anchor.getAttribute('data-bs-toggle') || anchor.getAttribute('data-toggle');
    if (toggle) return true;
    if (!isSameOrigin(anchor.href)) return true;
    return false;
}

function handleClick(event) {
    // If a legacy handler (jQuery, Bootstrap, inline onclick) already cancelled
    // this event, do NOT re-fire it through Inertia — that's how we get double
    // submissions / double navigations.
    if (event.defaultPrevented) return;
    if (!isPlainLeftClick(event)) return;
    const anchor = event.target.closest('a');
    if (!anchor) return;
    if (shouldIgnoreAnchor(anchor)) return;

    const url = anchor.href;
    event.preventDefault();

    try {
        router.visit(url, {
            preserveScroll: false,
            preserveState: false,
            onError: (errors) => {
                // eslint-disable-next-line no-console
                console.warn('[bridge] anchor visit onError', { url, errors });
                showToastFallback('Could not load that page. Falling back…');
                window.location.href = url;
            },
        });
    } catch (err) {
        // eslint-disable-next-line no-console
        console.warn('[bridge] fell back to native nav', { url, reason: 'anchor-visit-throw', err });
        window.location.href = url;
    }
}

function formHasFiles(form) {
    return Array.from(form.elements).some(
        (el) => el.type === 'file' && el.files && el.files.length > 0,
    );
}

function shouldIgnoreForm(form, action) {
    if (!form) return true;
    if (form.hasAttribute('data-no-inertia')) return true;
    // Forms whose handlers already invoked preventDefault are skipped at the
    // call site via event.defaultPrevented (jQuery $.ajax, etc.).
    if (form.target && form.target !== '' && form.target !== '_self') return true;
    if (!isSameOrigin(action)) return true;
    // Skip likely binary-download actions (export PDF, print, CSV).
    if (actionLooksLikeDownload(action)) return true;
    return false;
}

function handleSubmit(event) {
    // Critical: skip if a legacy jQuery / inline / Bootstrap submit handler
    // already called preventDefault. Otherwise we'd POST twice (once via
    // their $.ajax call, once via Inertia router.visit).
    if (event.defaultPrevented) return;

    const form = event.target.closest('form');
    if (!form) return;

    const action = form.action || window.location.href;

    // FORM SUBMISSIONS ARE DEFAULT-ON:
    // - data-no-inertia → skip (jQuery handlers without preventDefault,
    //   binary endpoints, etc.).
    // - target=_blank → skip (new tab).
    // - Cross-origin → skip.
    // - Likely-download actions (export/print/pdf/csv) → skip.
    if (shouldIgnoreForm(form, action)) return;

    const method = (form.getAttribute('method') || 'get').toLowerCase();
    event.preventDefault();

    const submitNative = (reason) => {
        // eslint-disable-next-line no-console
        console.warn('[bridge] fell back to native nav', { url: action, reason: reason || 'form-submit-fallback' });
        showToastFallback('Action failed via SPA; submitting natively.');
        form.submit();
    };

    try {
        const data = new FormData(form);
        const visitMethod = method === 'get' ? 'get' : method;
        const hasFiles = formHasFiles(form);
        const enctype = (form.getAttribute('enctype') || '').toLowerCase();
        const forceFormData = hasFiles || enctype === 'multipart/form-data';

        router.visit(action, {
            method: visitMethod,
            data,
            forceFormData,
            preserveScroll: false,
            preserveState: false,
            onError: (errors) => {
                // 422 validation errors are normal Inertia flow — the server
                // returns a redirect-back response with errors flashed; the
                // current Blade page just re-renders. Do NOT fall back to
                // native submit (that would re-POST the same bad data and
                // full-reload). Only surface a toast for non-validation hints.
                // eslint-disable-next-line no-console
                console.warn('[bridge] form submit onError (validation or otherwise)', { action, errors });
                // Intentionally no native fallback here.
            },
        });
    } catch (_) {
        submitNative('form-submit-throw');
    }
}

function injectLegacyStyles() {
    document
        .querySelectorAll('[data-uhms-legacy-style], [data-uhms-legacy-style-link]')
        .forEach((node) => node.remove());

    if (!props.styles) {
        return;
    }

    const template = document.createElement('template');
    template.innerHTML = props.styles;
    styleRunId += 1;

    const nodes = Array.from(template.content.querySelectorAll('style, link[rel="stylesheet"]'));

    nodes.forEach((source, index) => {
        if (source.tagName === 'STYLE') {
            const style = document.createElement('style');
            style.dataset.uhmsLegacyStyle = `${styleRunId}-${index}`;
            Array.from(source.attributes).forEach((attribute) => {
                if (attribute.name !== 'data-uhms-legacy-style') {
                    style.setAttribute(attribute.name, attribute.value);
                }
            });
            style.textContent = source.textContent;
            document.head.appendChild(style);
        } else {
            const link = document.createElement('link');
            link.dataset.uhmsLegacyStyleLink = `${styleRunId}-${index}`;
            Array.from(source.attributes).forEach((attribute) => {
                if (attribute.name !== 'data-uhms-legacy-style-link') {
                    link.setAttribute(attribute.name, attribute.value);
                }
            });
            document.head.appendChild(link);
        }
    });
}

function executeLegacyScripts() {
    document.querySelectorAll('script[data-uhms-legacy-script]').forEach((script) => script.remove());

    if (!props.scripts) {
        return;
    }

    const template = document.createElement('template');
    template.innerHTML = props.scripts;
    const scripts = Array.from(template.content.querySelectorAll('script'));

    if (!scripts.length) {
        return;
    }

    scriptRunId += 1;
    const originalAddEventListener = document.addEventListener.bind(document);

    document.addEventListener = function patchedAddEventListener(type, listener, options) {
        if (type === 'DOMContentLoaded' && document.readyState !== 'loading') {
            window.setTimeout(() => {
                if (typeof listener === 'function') {
                    listener.call(document, new Event('DOMContentLoaded'));
                } else if (listener && typeof listener.handleEvent === 'function') {
                    listener.handleEvent(new Event('DOMContentLoaded'));
                }
            }, 0);

            return undefined;
        }

        return originalAddEventListener(type, listener, options);
    };

    try {
        scripts.forEach((sourceScript, index) => {
            const script = document.createElement('script');
            script.dataset.uhmsLegacyScript = `${scriptRunId}-${index}`;

            Array.from(sourceScript.attributes).forEach((attribute) => {
                if (attribute.name !== 'data-uhms-legacy-script') {
                    script.setAttribute(attribute.name, attribute.value);
                }
            });

            if (!sourceScript.src) {
                script.textContent = sourceScript.textContent;
            }

            document.body.appendChild(script);
        });
    } finally {
        document.addEventListener = originalAddEventListener;
    }
}

function initialiseLegacyShell() {
    document.documentElement.classList.remove('uhms-loading');

    if (!window.jQuery) {
        return;
    }

    const $ = window.jQuery;

    if ($('.sidebar-overlay').length === 0) {
        $('body').append('<div class="sidebar-overlay"></div>');
    }

    $(document)
        .off('click.uhmsLegacySidebar', '.sidebar-menu a')
        .on('click.uhmsLegacySidebar', '.sidebar-menu a', function (event) {
            const item = $(this);

            if (!item.parent().hasClass('submenu')) {
                return;
            }

            event.preventDefault();

            if (!item.hasClass('subdrop')) {
                $('ul', item.parents('ul:first')).slideUp(250);
                $('a', item.parents('ul:first')).removeClass('subdrop');
                item.next('ul').slideDown(350);
                item.addClass('subdrop');
            } else {
                item.removeClass('subdrop');
                item.next('ul').slideUp(350);
            }
        });

    if ($.fn.select2) {
        $('.select2:not(.select2-hidden-accessible)').select2();
        $('.select:not(.select2-hidden-accessible)').select2({
            minimumResultsForSearch: -1,
            width: '100%',
        });
    }

    if (window.SimpleBar) {
        document.querySelectorAll('[data-simplebar]').forEach((element) => {
            if (!element.SimpleBar) {
                new window.SimpleBar(element);
            }
        });
    }
}

// Patch every <form> inside the legacy HTML so that programmatic
// form.submit() calls (e.g. onchange="this.form.submit()") fire a real
// submit event that bubbles up to the BladePage @submit handler.
// Without this patch those calls bypass the event listener entirely and
// trigger a full browser reload instead of an Inertia navigation.
function patchFormSubmitMethods() {
    if (!legacyRoot.value) return;
    legacyRoot.value.querySelectorAll('form').forEach((form) => {
        if (form.__uhmsSubmitPatched) return;
        form.__uhmsSubmitPatched = true;
        const nativeSubmit = HTMLFormElement.prototype.submit.bind(form);
        form.submit = function patchedSubmit() {
            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            const cancelled = !form.dispatchEvent(submitEvent);
            if (!cancelled) {
                nativeSubmit();
            }
        };
    });
}

async function afterPageSwap() {
    // Dispose any open Bootstrap modals BEFORE Vue swaps the v-html DOM,
    // preventing backdrop leaks and orphaned Bootstrap instances.
    cleanupBootstrapModals();
    await nextTick();
    injectLegacyStyles();
    initialiseLegacyShell();
    executeLegacyScripts();
    // Patch forms AFTER scripts run (scripts may add dynamic forms).
    await nextTick();
    patchFormSubmitMethods();
}

onMounted(afterPageSwap);
onUnmounted(() => {
    cleanupBootstrapModals();
    document
        .querySelectorAll('[data-uhms-legacy-style], [data-uhms-legacy-style-link]')
        .forEach((node) => node.remove());
});

watch(
    () => [props.html, props.scripts, props.styles, props.url],
    afterPageSwap,
);
</script>

<style scoped>
.uhms-inertia-legacy-page :deep(.main-wrapper) {
    min-height: 100vh;
}
</style>
