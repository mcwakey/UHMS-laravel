<script setup>
/**
 * Billing → Credit Notes & Write-offs (Inertia). Lists issued credit notes
 * and write-offs, with a cancel action (requires a reason).
 */
import { reactive, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    creditNotes: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    typeOptions: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const form = reactive({
    search: props.filters.search ?? '',
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
});

function applyFilters() {
    router.get(props.routes.index, form, { preserveState: true, preserveScroll: true, replace: true });
}
function clearFilters() {
    form.search = '';
    form.type = '';
    form.status = '';
    router.get(props.routes.index, {}, { preserveScroll: true });
}

function formatMoney(value) {
    const n = Number(value || 0);
    return '\u20B5' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// --- Cancel modal ---
const cancelTarget = ref(null);
const cancelForm = useForm({ reason: '' });

function openCancel(cn) {
    cancelTarget.value = cn;
    cancelForm.reset();
    cancelForm.clearErrors();
}
function closeCancel() {
    cancelTarget.value = null;
}
function submitCancel() {
    if (!cancelTarget.value) return;
    cancelForm.patch(cancelTarget.value.cancel_url, {
        preserveScroll: true,
        onSuccess: () => closeCancel(),
    });
}
</script>

<template>
    <AppLayout title="Credit Notes">
        <div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-receipt-refund me-2"></i>Credit Notes &amp; Write-offs</h4>
            </div>
            <div class="d-flex gap-2">
                <Link v-if="can.create" :href="routes.create" class="btn btn-primary btn-md">
                    <i class="ti ti-plus me-1"></i>New Credit Note
                </Link>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-soft-info rounded me-3">
                                <i class="ti ti-receipt-refund fs-4 text-info"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ formatMoney(stats.total_credit_notes) }}</h3>
                                <p class="text-muted mb-0">Total Credit Notes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-soft-danger rounded me-3">
                                <i class="ti ti-eraser fs-4 text-danger"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ formatMoney(stats.total_write_offs) }}</h3>
                                <p class="text-muted mb-0">Total Write-offs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-soft-primary rounded me-3">
                                <i class="ti ti-list-numbers fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ Number(stats.count || 0).toLocaleString() }}</h3>
                                <p class="text-muted mb-0">Active Records</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <form @submit.prevent="applyFilters" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Search</label>
                        <input v-model="form.search" type="text" class="form-control form-control-sm" placeholder="Credit note #, invoice, patient...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Type</label>
                        <select v-model="form.type" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">All Types</option>
                            <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select v-model="form.status" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">All Statuses</option>
                            <option value="issued">Issued</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearFilters"><i class="ti ti-x"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Credit Note #</th>
                                <th>Invoice</th>
                                <th>Patient</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Issued By</th>
                                <th>Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="cn in creditNotes.data" :key="cn.id">
                                <td class="fw-medium">{{ cn.credit_note_number }}</td>
                                <td>
                                    <Link v-if="cn.invoice_url" :href="cn.invoice_url" class="text-primary">{{ cn.invoice_number }}</Link>
                                    <span v-else>—</span>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ cn.patient_name }}</div>
                                    <small class="text-muted">{{ cn.patient_number }}</small>
                                </td>
                                <td><span :class="`badge bg-soft-${cn.type.color}`">{{ cn.type.label }}</span></td>
                                <td class="text-end fw-medium">{{ formatMoney(cn.amount) }}</td>
                                <td><small>{{ cn.reason }}</small></td>
                                <td>
                                    <span class="badge" :class="cn.status === 'issued' ? 'bg-success' : 'bg-secondary'">
                                        {{ cn.status === 'issued' ? 'Issued' : 'Cancelled' }}
                                    </span>
                                </td>
                                <td><small>{{ cn.issued_by ?? '—' }}</small></td>
                                <td>{{ cn.created_at_display }}</td>
                                <td class="text-center">
                                    <button v-if="cn.can_cancel" type="button" class="btn btn-sm btn-outline-danger" @click="openCancel(cn)">
                                        <i class="ti ti-x me-1"></i>Cancel
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!creditNotes.data.length">
                                <td colspan="10" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-receipt-refund fs-1 d-block mb-2"></i>
                                        No credit notes found.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-if="creditNotes.links && creditNotes.links.length > 3" class="card-footer">
                <nav>
                    <ul class="pagination mb-0 justify-content-end">
                        <li v-for="(link, idx) in creditNotes.links" :key="idx" class="page-item"
                            :class="{ active: link.active, disabled: !link.url }">
                            <Link v-if="link.url" :href="link.url" class="page-link" preserve-scroll v-html="link.label" />
                            <span v-else class="page-link" v-html="link.label" />
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Cancel modal -->
        <div v-if="cancelTarget" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel {{ cancelTarget.credit_note_number }}</h5>
                        <button type="button" class="btn-close" @click="closeCancel"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Cancelling this credit note will reverse its effect on the invoice balance. Provide a reason.</p>
                        <label class="form-label small">Reason <span class="text-danger">*</span></label>
                        <textarea v-model="cancelForm.reason" class="form-control" rows="3" placeholder="Reason for cancellation"></textarea>
                        <div v-if="cancelForm.errors.reason" class="text-danger small mt-1">{{ cancelForm.errors.reason }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="closeCancel">Keep</button>
                        <button type="button" class="btn btn-danger" :disabled="cancelForm.processing" @click="submitCancel">
                            <i class="ti ti-x me-1"></i>Cancel Credit Note
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
