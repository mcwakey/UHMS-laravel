<script setup>
/**
 * UhmsConfirmDialog — UHMS-styled replacement for window.confirm / window.alert.
 *
 * Singleton dialog mounted once at the AppLayout root. Use the `useConfirm`
 * composable to invoke it from anywhere in the Vue tree:
 *
 *   import { useConfirm } from '@/Composables/useConfirm';
 *   const { confirm } = useConfirm();
 *   if (!(await confirm({ title: 'Cancel invoice?', message: '...', variant: 'danger' }))) return;
 *
 * Driven by a shared reactive store so it never re-mounts.
 *
 * No native browser popups, no v-html, no `window.location.*`. Safe to render
 * under the Inertia bridge leak-guard test.
 */
import { computed, nextTick, onMounted, ref, useTemplateRef, watch } from 'vue';
import { uhmsConfirmState, resolveConfirm } from '../Composables/useConfirm';

const dialogEl = useTemplateRef('dialogEl');
const confirmBtn = useTemplateRef('confirmBtn');
let bsModal = null;

const variant = computed(() => uhmsConfirmState.options?.variant || 'primary');

const iconClass = computed(() => {
    const map = {
        danger: 'ti ti-alert-triangle text-danger',
        warning: 'ti ti-alert-circle text-warning',
        success: 'ti ti-circle-check text-success',
        info: 'ti ti-info-circle text-info',
        primary: 'ti ti-help-circle text-primary',
    };
    return map[variant.value] || map.primary;
});

const confirmBtnClass = computed(() => {
    const map = {
        danger: 'btn-danger',
        warning: 'btn-warning',
        success: 'btn-success',
        info: 'btn-info',
        primary: 'btn-primary',
    };
    return `btn ${map[variant.value] || 'btn-primary'}`;
});

const headerClass = computed(() => {
    const map = {
        danger: 'border-danger',
        warning: 'border-warning',
        success: 'border-success',
        info: 'border-info',
        primary: 'border-primary',
    };
    return map[variant.value] || 'border-primary';
});

function openModal() {
    if (!dialogEl.value || !window.bootstrap?.Modal) return;
    bsModal = window.bootstrap.Modal.getOrCreateInstance(dialogEl.value, {
        backdrop: 'static',
        keyboard: !uhmsConfirmState.options?.requireConfirmation,
    });
    bsModal.show();
    nextTick(() => {
        if (confirmBtn.value) confirmBtn.value.focus();
    });
}

function closeModal() {
    if (bsModal) bsModal.hide();
}

function onCancel() {
    resolveConfirm(false);
    closeModal();
}

function onConfirm() {
    resolveConfirm(true);
    closeModal();
}

onMounted(() => {
    if (!dialogEl.value) return;
    dialogEl.value.addEventListener('hidden.bs.modal', () => {
        // Resolve with false if dismissed via backdrop/escape without a button.
        if (uhmsConfirmState.open) {
            resolveConfirm(false);
        }
    });
});

watch(
    () => uhmsConfirmState.open,
    (open) => {
        if (open) openModal();
        else closeModal();
    }
);
</script>

<template>
    <div
        ref="dialogEl"
        class="modal fade uhms-confirm-dialog"
        tabindex="-1"
        aria-labelledby="uhmsConfirmTitle"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-top border-3" :class="headerClass">
                <div class="modal-header py-3">
                    <h5 id="uhmsConfirmTitle" class="modal-title d-flex align-items-center gap-2 mb-0">
                        <i :class="iconClass" style="font-size: 1.4rem"></i>
                        <span>{{ uhmsConfirmState.options?.title || 'Confirm action' }}</span>
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        aria-label="Close"
                        @click="onCancel"
                    ></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2 text-body">{{ uhmsConfirmState.options?.message || 'Are you sure you want to proceed?' }}</p>
                    <p
                        v-if="uhmsConfirmState.options?.details"
                        class="small text-muted mb-0"
                    >
                        {{ uhmsConfirmState.options.details }}
                    </p>
                </div>
                <div class="modal-footer py-2">
                    <button
                        v-if="!uhmsConfirmState.options?.alertOnly"
                        type="button"
                        class="btn btn-light"
                        @click="onCancel"
                    >
                        {{ uhmsConfirmState.options?.cancelLabel || 'Cancel' }}
                    </button>
                    <button
                        ref="confirmBtn"
                        type="button"
                        :class="confirmBtnClass"
                        @click="onConfirm"
                    >
                        <i v-if="variant === 'danger'" class="ti ti-trash me-1"></i>
                        {{ uhmsConfirmState.options?.confirmLabel || 'Confirm' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
