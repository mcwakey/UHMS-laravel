<template>
    <Head :title="title" />
    <div
        class="uhms-inertia-legacy-page"
        v-html="html"
    />
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { nextTick, onMounted, onUnmounted, watch } from 'vue';
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

async function afterPageSwap() {
    // Dispose any open Bootstrap modals BEFORE Vue swaps the v-html DOM,
    // preventing backdrop leaks and orphaned Bootstrap instances.
    cleanupBootstrapModals();
    await nextTick();
    injectLegacyStyles();
    initialiseLegacyShell();
    executeLegacyScripts();
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
