<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            @click.self="cancel"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ dialog.title }}</h5>
                        <button type="button" class="btn-close" :aria-label="dialog.cancelLabel" @click="cancel"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">{{ dialog.message }}</p>
                        <p v-if="dialog.details" class="text-muted small mt-2 mb-0">{{ dialog.details }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="cancel">
                            {{ dialog.cancelLabel }}
                        </button>
                        <button type="button" :class="confirmButtonClass" @click="confirm">
                            {{ dialog.confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="visible" class="modal-backdrop fade show"></div>
    </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { CONFIRM_EVENT } from '../Composables/useConfirm';

const visible = ref(false);
const resolver = ref(null);
const dialog = reactive({
    title: 'Confirm action',
    message: 'Are you sure?',
    details: '',
    variant: 'primary',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
});

const confirmButtonClass = computed(() => {
    const allowed = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
    const variant = allowed.includes(dialog.variant) ? dialog.variant : 'primary';

    return ['btn', `btn-${variant}`];
});

function close(result) {
    visible.value = false;
    document.body.classList.remove('modal-open');

    const resolve = resolver.value;
    resolver.value = null;
    if (resolve) {
        resolve(result);
    }
}

function confirm() {
    close(true);
}

function cancel() {
    close(false);
}

function open(event) {
    const options = event.detail?.options || {};
    resolver.value = event.detail?.resolve || null;

    dialog.title = options.title || 'Confirm action';
    dialog.message = options.message || 'Are you sure?';
    dialog.details = options.details || '';
    dialog.variant = options.variant || 'primary';
    dialog.confirmLabel = options.confirmLabel || 'Confirm';
    dialog.cancelLabel = options.cancelLabel || 'Cancel';

    visible.value = true;
    document.body.classList.add('modal-open');
}

function handleKeydown(event) {
    if (!visible.value || event.key !== 'Escape') return;
    cancel();
}

onMounted(() => {
    window.addEventListener(CONFIRM_EVENT, open);
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener(CONFIRM_EVENT, open);
    window.removeEventListener('keydown', handleKeydown);
});
</script>
