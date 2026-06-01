<script setup>
/**
 * Billing → Issue Credit Note / Write-off (Inertia). Select an open invoice,
 * choose type, enter amount (capped by the available-to-credit value fetched
 * live), reason and notes.
 */
import { reactive, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    invoices: { type: Array, default: () => [] },
    selected: { type: Object, default: null },
    typeOptions: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
});

const available = ref(props.selected ? Number(props.selected.available || 0) : null);

const form = useForm({
    invoice_id: props.selected ? props.selected.id : '',
    type: props.typeOptions[0]?.value ?? 'credit_note',
    amount: '',
    reason: '',
    notes: '',
});

function formatMoney(value) {
    const n = Number(value || 0);
    return '\u20B5' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function fetchAvailable(invoiceId) {
    if (!invoiceId) { available.value = null; return; }
    try {
        const url = `${props.routes.available}?invoice_id=${invoiceId}`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        available.value = Number(data.available || 0);
    } catch (_) {
        available.value = null;
    }
}

watch(() => form.invoice_id, (id) => {
    form.amount = '';
    fetchAvailable(id);
});

function submit() {
    form.post(props.routes.store, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="New Credit Note">
        <div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-receipt-refund me-2"></i>Issue Credit Note / Write-off</h4>
            </div>
            <div class="d-flex gap-2">
                <Link :href="routes.index" class="btn btn-outline-secondary btn-md">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </Link>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="form-label">Invoice <span class="text-danger">*</span></label>
                                <select v-model="form.invoice_id" class="form-select" :class="{ 'is-invalid': form.errors.invoice_id }">
                                    <option value="">— Select an open invoice —</option>
                                    <option v-for="inv in invoices" :key="inv.id" :value="inv.id">
                                        {{ inv.invoice_number }} — {{ inv.patient_name }} ({{ formatMoney(inv.balance) }})
                                    </option>
                                </select>
                                <div v-if="form.errors.invoice_id" class="invalid-feedback">{{ form.errors.invoice_id }}</div>
                            </div>

                            <div v-if="available !== null" class="alert alert-info py-2">
                                <i class="ti ti-info-circle me-1"></i>
                                Available to credit: <strong>{{ formatMoney(available) }}</strong>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select v-model="form.type" class="form-select" :class="{ 'is-invalid': form.errors.type }">
                                    <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                                <div v-if="form.errors.type" class="invalid-feedback">{{ form.errors.type }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">&#8373;</span>
                                    <input v-model="form.amount" type="number" step="0.01" min="0.01"
                                           :max="available ?? undefined"
                                           class="form-control" :class="{ 'is-invalid': form.errors.amount }"
                                           placeholder="0.00">
                                </div>
                                <div v-if="form.errors.amount" class="text-danger small mt-1">{{ form.errors.amount }}</div>
                                <small v-if="available !== null && Number(form.amount) > available" class="text-danger">
                                    Amount exceeds available balance.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                <input v-model="form.reason" type="text" class="form-control"
                                       :class="{ 'is-invalid': form.errors.reason }" maxlength="255"
                                       placeholder="e.g. Service not rendered, goodwill adjustment">
                                <div v-if="form.errors.reason" class="invalid-feedback">{{ form.errors.reason }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea v-model="form.notes" class="form-control" rows="3" maxlength="1000"
                                          :class="{ 'is-invalid': form.errors.notes }" placeholder="Optional internal notes"></textarea>
                                <div v-if="form.errors.notes" class="invalid-feedback">{{ form.errors.notes }}</div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <Link :href="routes.index" class="btn btn-outline-secondary">Cancel</Link>
                                <button type="submit" class="btn btn-primary"
                                        :disabled="form.processing || !form.invoice_id || (available !== null && Number(form.amount) > available)">
                                    <i class="ti ti-check me-1"></i>Issue
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
