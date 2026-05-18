<script setup>
/**
 * AppLayout — the shell for true (non-legacy) Inertia pages.
 *
 * Architecture
 * ------------
 * Legacy Blade pages are routed through Pages/Legacy/BladePage.vue which
 * injects the FULL Blade layout HTML (sidebar + header + body) per visit.
 *
 * True Inertia pages use this layout instead: the sidebar + header HTML is
 * shared once via `legacyChrome` (HandleInertiaRequests) and injected into
 * fixed mount points on first mount only. Subsequent Inertia visits keep the
 * existing chrome DOM in place — no flicker, no re-init.
 *
 * Slots
 * -----
 * - default: page content (rendered inside `.page-wrapper > .content`).
 * - header-actions (optional): bottom of the page-header area.
 */
import { computed, onMounted, ref, watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

defineProps({
    /** Page title (rendered at top of content area). */
    title: { type: String, default: '' },
});

const page = usePage();

const chromeRef = ref(null);
const chromeInjected = ref(false);

const flashSuccess = computed(() => page.props?.flash?.success || null);
const flashError = computed(() => page.props?.flash?.error || null);

function injectChrome() {
    if (chromeInjected.value) return;
    const html = page.props?.legacyChrome;
    if (!html || !chromeRef.value) return;
    chromeRef.value.innerHTML = html;
    chromeInjected.value = true;

    // Re-trigger legacy template initializers that bind on DOMContentLoaded
    // (sidebar collapse, menu state, etc.). Wrapped because a missing init
    // function must not break SPA navigation.
    try {
        // eslint-disable-next-line no-undef
        if (typeof window.uhmsInitTemplate === 'function') {
            window.uhmsInitTemplate();
        } else {
            // Best-effort: dispatch a synthetic DOMContentLoaded so any
            // late-loading template script picks up the new chrome nodes.
            document.dispatchEvent(new Event('uhms:chrome-ready'));
        }
    } catch (e) {
        // eslint-disable-next-line no-console
        console.warn('[AppLayout] chrome init failed', e);
    }
}

onMounted(injectChrome);
// If the legacyChrome prop arrives after first mount (lazy share), inject then.
watch(() => page.props?.legacyChrome, injectChrome);
</script>

<template>
    <!-- Sidebar + header are injected here, once, from shared `legacyChrome`. -->
    <div ref="chromeRef" class="uhms-inertia-chrome"></div>

    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content">
                <!-- Flash messages (reactive, no v-html). -->
                <div
                    v-if="flashSuccess"
                    class="alert alert-success alert-dismissible fade show"
                    role="alert"
                >
                    <i class="ti ti-circle-check me-1"></i>
                    {{ flashSuccess }}
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>
                </div>
                <div
                    v-if="flashError"
                    class="alert alert-danger alert-dismissible fade show"
                    role="alert"
                >
                    <i class="ti ti-alert-circle me-1"></i>
                    {{ flashError }}
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>
                </div>

                <slot />
            </div>
        </div>

        <div class="footer text-center bg-white p-2 border-top">
            <p class="text-dark mb-0">
                {{ new Date().getFullYear() }} &copy;
                <Link href="/" class="link-primary">UHMS</Link>
                - Ultimate Hospital Management System
            </p>
        </div>
    </div>
</template>
