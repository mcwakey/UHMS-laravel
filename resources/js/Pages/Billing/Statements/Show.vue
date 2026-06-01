<script setup>
/**
 * Billing → Patient Statement detail (Inertia). Shows the running ledger of
 * charges and payments, summary totals, date filters and PDF export.
 */
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    patient: { type: Object, required: true },
    summary: { type: Object, required: true },
    ledger: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
});

const form = reactive({
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

function applyFilters() {
    router.get(props.routes.self, form, { preserveState: true, preserveScroll: true, replace: true });
}
function clearFilters() {
    form.date_from = '';
    form.date_to = '';
    router.get(props.routes.self, {}, { preserveScroll: true });
}

function formatMoney(value) {
    const n = Number(value || 0);
    return '\u20B5' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function pdfUrl() {
    const params = new URLSearchParams();
    if (form.date_from) params.set('date_from', form.date_from);
    if (form.date_to) params.set('date_to', form.date_to);
    const qs = params.toString();
    return props.routes.pdf + (qs ? `?${qs}` : '');
}
</script>

<template>
    <AppLayout :title="`Statement — ${patient.name}`">
        <div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-file-text me-2"></i>Statement — {{ patient.name }}</h4>
                <small class="text-muted">{{ patient.patient_number }} <span v-if="patient.phone">· {{ patient.phone }}</span></small>
            </div>
            <div class="d-flex gap-2">
                <a :href="pdfUrl()" class="btn btn-outline-danger btn-md">
                    <i class="ti ti-file-type-pdf me-1"></i>Export PDF
                </a>
                <Link :href="routes.index" class="btn btn-outline-secondary btn-md">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </Link>
            </div>
        </div>

        <!-- Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="fw-bold mb-0">{{ formatMoney(summary.total_charges) }}</h3>
                        <p class="text-muted mb-0">Total Charges</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="fw-bold mb-0 text-success">{{ formatMoney(summary.total_payments) }}</h3>
                        <p class="text-muted mb-0">Total Payments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="fw-bold mb-0" :class="summary.balance_due > 0 ? 'text-danger' : ''">{{ formatMoney(summary.balance_due) }}</h3>
                        <p class="text-muted mb-0">Balance Due</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="fw-bold mb-0">{{ summary.invoice_count }} / {{ summary.payment_count }}</h3>
                        <p class="text-muted mb-0">Invoices / Payments</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date filter -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <form @submit.prevent="applyFilters" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">From</label>
                        <input v-model="form.date_from" type="date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">To</label>
                        <input v-model="form.date_to" type="date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearFilters"><i class="ti ti-x"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ledger -->
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">Transaction Ledger</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th class="text-end">Charges</th>
                                <th class="text-end">Payments</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(e, idx) in ledger" :key="idx">
                                <td>{{ e.date }}</td>
                                <td>
                                    <span class="badge me-1" :class="e.type === 'charge' ? 'bg-soft-primary' : 'bg-soft-success'">
                                        {{ e.type === 'charge' ? 'Charge' : 'Payment' }}
                                    </span>
                                    {{ e.reference }}
                                </td>
                                <td>{{ e.description }}</td>
                                <td class="text-end">{{ e.charges > 0 ? formatMoney(e.charges) : '—' }}</td>
                                <td class="text-end text-success">{{ e.payments > 0 ? formatMoney(e.payments) : '—' }}</td>
                                <td class="text-end fw-medium">{{ formatMoney(e.balance) }}</td>
                            </tr>
                            <tr v-if="!ledger.length">
                                <td colspan="6" class="text-center py-4 text-muted">No transactions in this period.</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="ledger.length" class="table-light">
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">Totals</td>
                                <td class="text-end">{{ formatMoney(summary.total_charges) }}</td>
                                <td class="text-end text-success">{{ formatMoney(summary.total_payments) }}</td>
                                <td class="text-end" :class="summary.balance_due > 0 ? 'text-danger' : ''">{{ formatMoney(summary.balance_due) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
