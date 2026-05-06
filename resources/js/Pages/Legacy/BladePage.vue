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
    title: {
        type: String,
        default: 'UHMS',
    },
    url: {
        type: String,
        default: '',
    },
});

const legacyRoot = ref(null);
let scriptRunId = 0;

function isPlainLeftClick(event) {
    return event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
}

function sameOriginUrl(href) {
    try {
        const url = new URL(href, window.location.href);
        return url.origin === window.location.origin ? url : null;
    } catch (error) {
        return null;
    }
}

function shouldIgnoreAnchor(anchor) {
    const href = anchor.getAttribute('href') || '';
    const url = sameOriginUrl(anchor.href);

    return !href
        || href === '#'
        || href.startsWith('#')
        || href.startsWith('javascript:')
        || href.startsWith('mailto:')
        || href.startsWith('tel:')
        || anchor.hasAttribute('download')
        || anchor.target
        || anchor.closest('[data-bs-toggle]')
        || anchor.dataset.spaIgnore === 'true'
        || anchor.dataset.inertiaIgnore === 'true'
        || (url && (url.pathname.includes('/print') || url.searchParams.get('export') === 'pdf'));
}

function handleClick(event) {
    if (event.defaultPrevented || !isPlainLeftClick(event)) {
        return;
    }

    const anchor = event.target.closest('a[href]');

    if (!anchor || !legacyRoot.value?.contains(anchor) || shouldIgnoreAnchor(anchor)) {
        return;
    }

    const url = sameOriginUrl(anchor.href);

    if (!url) {
        return;
    }

    if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
        return;
    }

    event.preventDefault();

    router.visit(url.pathname + url.search + url.hash, {
        method: 'get',
        preserveScroll: false,
        preserveState: false,
    });
}

function formDataToObject(formData) {
    const data = {};

    for (const [key, value] of formData.entries()) {
        if (key in data) {
            data[key] = Array.isArray(data[key]) ? [...data[key], value] : [data[key], value];
        } else {
            data[key] = value;
        }
    }

    return data;
}

function formHasFiles(form) {
    return Array.from(form.querySelectorAll('input[type="file"]')).some((input) => input.files.length > 0);
}

function handleSubmit(event) {
    if (event.defaultPrevented) {
        return;
    }

    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.dataset.spaIgnore === 'true' || form.dataset.inertiaIgnore === 'true' || form.target) {
        return;
    }

    const actionUrl = sameOriginUrl(form.action || window.location.href);

    if (!actionUrl) {
        return;
    }

    event.preventDefault();

    let formData;

    try {
        formData = new FormData(form, event.submitter || undefined);
    } catch (error) {
        formData = new FormData(form);
    }

    const method = (form.method || 'get').toLowerCase();
    const hasFiles = formHasFiles(form);

    router.visit(actionUrl.pathname + actionUrl.search, {
        method,
        data: hasFiles ? formData : formDataToObject(formData),
        forceFormData: hasFiles,
        preserveScroll: false,
        preserveState: false,
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

async function afterPageSwap() {
    // Dispose any open Bootstrap modals BEFORE Vue swaps the v-html DOM,
    // preventing backdrop leaks and orphaned Bootstrap instances.
    cleanupBootstrapModals();
    await nextTick();
    initialiseLegacyShell();
    executeLegacyScripts();
}

onMounted(afterPageSwap);
onUnmounted(cleanupBootstrapModals);

watch(
    () => [props.html, props.scripts, props.url],
    afterPageSwap,
);
</script>

<style scoped>
.uhms-inertia-legacy-page :deep(.main-wrapper) {
    min-height: 100vh;
}
</style>
