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
    scriptsEncoded: {
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

// ── Sidebar scroll persistence ───────────────────────────────────────────────
// The whole legacy layout (including the sidebar) is re-rendered via v-html on
// every Inertia navigation, and initialiseLegacyShell() re-creates SimpleBar
// from scratch — which resets the sidebar scroll to the top. We capture the
// sidebar scroll position before each visit and restore it after the new
// SimpleBar instance is built, so the active menu item stays in view.
const SIDEBAR_SCROLL_KEY = 'uhms:sidebarScroll';

function getSidebarScrollEl() {
    const inner = document.querySelector('.sidebar-inner');
    if (!inner) return null;
    // SimpleBar moves the scrollable content into .simplebar-content-wrapper;
    // fall back to the element itself if SimpleBar hasn't initialised yet.
    return inner.querySelector('.simplebar-content-wrapper') || inner;
}

function saveSidebarScroll() {
    const el = getSidebarScrollEl();
    if (!el) return;
    try {
        window.sessionStorage.setItem(SIDEBAR_SCROLL_KEY, String(el.scrollTop || 0));
    } catch (_) {
        /* sessionStorage unavailable — ignore */
    }
}

function restoreSidebarScroll() {
    let saved = 0;
    try {
        saved = parseInt(window.sessionStorage.getItem(SIDEBAR_SCROLL_KEY) || '0', 10) || 0;
    } catch (_) {
        saved = 0;
    }
    if (saved <= 0) return;
    const apply = () => {
        const el = getSidebarScrollEl();
        if (el) {
            el.scrollTop = saved;
        }
    };
    // Apply immediately and again on the next frame, since SimpleBar may finish
    // laying out its content wrapper a tick after construction.
    apply();
    window.requestAnimationFrame(apply);
}

let sidebarScrollPersistenceBound = false;
function bindSidebarScrollPersistence() {
    if (sidebarScrollPersistenceBound) return;
    sidebarScrollPersistenceBound = true;
    // Fires while the OLD sidebar DOM is still present, before the request runs.
    router.on('before', saveSidebarScroll);
}

function decodedLegacyScripts() {
    if (!props.scriptsEncoded) {
        return props.scripts;
    }

    try {
        const binary = window.atob(props.scriptsEncoded);
        const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0));
        return new TextDecoder().decode(bytes);
    } catch (_) {
        return props.scripts;
    }
}

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
    if (form.hasAttribute('data-spa-ignore')) return true;
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
    const preserveScroll = form.hasAttribute('data-preserve-scroll');
    event.preventDefault();

    const submitNative = (reason) => {
        // eslint-disable-next-line no-console
        console.warn('[bridge] fell back to native nav', { url: action, reason: reason || 'form-submit-fallback' });
        showToastFallback('Action failed via SPA; submitting natively.');
        form.submit();
    };

    try {
        const hasFiles = formHasFiles(form);
        const enctype = (form.getAttribute('enctype') || '').toLowerCase();
        const forceFormData = hasFiles || enctype === 'multipart/form-data';

        if (method === 'get') {
            // For GET forms (filter/search), manually convert FormData to a URL
            // query string. Passing FormData directly to router.visit() for GET
            // requests is unreliable in Inertia v2+ and may silently drop params,
            // causing the server to receive an unfiltered request.
            const params = new URLSearchParams();
            new FormData(form).forEach((value, key) => {
                params.append(key, value instanceof File ? '' : String(value));
            });
            const baseUrl = action.split('?')[0];
            const qs = params.toString();
            const visitUrl = qs ? `${baseUrl}?${qs}` : baseUrl;

            router.visit(visitUrl, {
                method: 'get',
                preserveScroll,
                preserveState: false,
                onError: (errors) => {
                    // eslint-disable-next-line no-console
                    console.warn('[bridge] GET form visit error', { visitUrl, errors });
                },
            });
        } else {
            const data = new FormData(form);

            router.visit(action, {
                method,
                data,
                forceFormData,
                preserveScroll,
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
        }
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

function waitForLegacyGlobals(timeout = 1500) {
    if (window.jQuery) {
        return Promise.resolve();
    }

    const startedAt = Date.now();

    return new Promise((resolve) => {
        const tick = () => {
            if (window.jQuery || Date.now() - startedAt >= timeout) {
                resolve();
                return;
            }

            window.setTimeout(tick, 25);
        };

        tick();
    });
}

function executeScriptNode(sourceScript, runId, index) {
    return new Promise((resolve) => {
        const script = document.createElement('script');
        script.dataset.uhmsLegacyScript = `${runId}-${index}`;

        Array.from(sourceScript.attributes).forEach((attribute) => {
            if (attribute.name !== 'data-uhms-legacy-script') {
                script.setAttribute(attribute.name, attribute.value);
            }
        });

        script.async = false;

        if (sourceScript.src) {
            script.onload = () => resolve();
            script.onerror = () => {
                // eslint-disable-next-line no-console
                console.warn('[bridge] legacy script failed to load', sourceScript.src);
                resolve();
            };
        } else {
            script.textContent = sourceScript.textContent;
        }

        document.body.appendChild(script);

        if (!sourceScript.src) {
            resolve();
        }
    });
}

async function executeLegacyScripts() {
    document.querySelectorAll('script[data-uhms-legacy-script]').forEach((script) => script.remove());

    const scriptsHtml = decodedLegacyScripts();

    if (!scriptsHtml) {
        return;
    }

    const template = document.createElement('template');
    template.innerHTML = scriptsHtml;
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
        for (const [index, sourceScript] of scripts.entries()) {
            // eslint-disable-next-line no-await-in-loop
            await executeScriptNode(sourceScript, scriptRunId, index);
        }
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

    // Restore the sidebar scroll position now that SimpleBar has rebuilt its
    // scroll container for the freshly-rendered sidebar.
    restoreSidebarScroll();
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
    await waitForLegacyGlobals();
    initialiseLegacyShell();
    await executeLegacyScripts();
    // Patch forms AFTER scripts run (scripts may add dynamic forms).
    await nextTick();
    patchFormSubmitMethods();
    // Notify page modules (Vite ES modules only execute once per session) that
    // a fresh legacy DOM is mounted so they can re-initialise their bindings.
    document.dispatchEvent(new CustomEvent('uhms:legacy-page-mounted'));
}

onMounted(afterPageSwap);
onMounted(bindSidebarScrollPersistence);
onUnmounted(() => {
    cleanupBootstrapModals();
    document
        .querySelectorAll('[data-uhms-legacy-style], [data-uhms-legacy-style-link]')
        .forEach((node) => node.remove());
});

watch(
    () => [props.html, props.scripts, props.scriptsEncoded, props.styles, props.url],
    afterPageSwap,
);
</script>

<style scoped>
.uhms-inertia-legacy-page :deep(.main-wrapper) {
    min-height: 100vh;
}
</style>
