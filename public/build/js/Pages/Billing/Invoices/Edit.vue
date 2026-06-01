<script setup>
/**
 * Billing → Edit Invoice header (Inertia). Edit billing type, sponsor (for
 * corporate billing), tax, due date and notes. Line items are managed
 * elsewhere.
 */
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    invoice: { type: Object, required: true },
    billingTypeOptions: { type: Array, default: () => [] },
    sponsors: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
});

const form = useForm({
    billing_type: props.invoice.billing_type ?? '',
    sponsor_id: props.invoice.sponsor_id ?? '',
    tax_amount: props.invoice.tax_amount ?? 0,
    due_date: props.invoice.due_date ?? '',
    notes: props.invoice.notes ?? '',
});

const isCorporate = computed(() => form.billing_type === 'corporate');

function formatMoney(value) {
    const n = Number(value || 0);
    return '\u20B5' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function submit() {
    form.put(props.routes.update, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="`Edit ${invoice.invoice_number}`">
        <div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-edit me-2"></i>Edit Invoice {{ invoice.invoice_number }}</h4>
                <small class="text-muted">{{ invoice.patient_name }} · Total {{ formatMoney(invoice.total_amount) }} · Balance {{ formatMoney(invoice.balance) }}</small>
            </div>
            <div class="d-flex gap-2">
                <Link :href="routes.show" class="btn btn-outline-secondary btn-md">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </Link>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Billing Type <span class="text-danger">*</span></label>
                                    <select v-model="form.billing_type" class="form-select" :class="{ 'is-invalid': form.errors.billing_type }">
                                        <option v-for="opt in billingTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                    </select>
                                    <div v-if="form.errors.billing_type" class="invalid-feedback">{{ form.errors.billing_type }}</div>
                                </div>
                                <div v-if="isCorporate" class="col-md-6">
                                    <label class="form-label">Corporate Sponsor</label>
                                    <select v-model="form.sponsor_id" class="form-select" :class="{ 'is-invalid': form.errors.sponsor_id }">
                                        <option value="">— Select sponsor —</option>
                                        <option v-for="s in sponsors" :key="s.id" :value="s.id">{{ s.name }}</option>
                                    </select>
                                    <div v-if="form.errors.sponsor_id" class="invalid-feedback">{{ form.errors.sponsor_id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tax Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">&#8373;</span>
                                        <input v-model="form.tax_amount" type="number" step="0.01" min="0" class="form-control" :class="{ 'is-invalid': form.errors.tax_amount }">
                                    </div>
                                    <div v-if="form.errors.tax_amount" class="text-danger small mt-1">{{ form.errors.tax_amount }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Due Date</label>
                                    <input v-model="form.due_date" type="date" class="form-control" :class="{ 'is-invalid': form.errors.due_date }">
                                    <div v-if="form.errors.due_date" class="invalid-feedback">{{ form.errors.due_date }}</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea v-model="form.notes" class="form-control" rows="3" maxlength="2000" :class="{ 'is-invalid': form.errors.notes }"></textarea>
                                    <div v-if="form.errors.notes" class="invalid-feedback">{{ form.errors.notes }}</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <Link :href="routes.show" class="btn btn-outline-secondary">Cancel</Link>
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                    <i class="ti ti-check me-1"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
