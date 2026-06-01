<script setup>
/**
 * Billing → AR Aging report (Inertia). Buckets outstanding balances by
 * overdue age, with filters by billing type and sponsor and a PDF export.
 */
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    aging: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    billingTypeOptions: { type: Array, default: () => [] },
    sponsors: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
});

const form = reactive({
    billing_type: props.filters.billing_type ?? '',
    sponsor_id: props.filters.sponsor_id ?? '',
});

function applyFilters() {
    router.get(props.routes.aging, form, { preserveState: true, preserveScroll: true, replace: true });
}
function clearFilters() {
    form.billing_type = '';
    form.sponsor_id = '';
    router.get(props.routes.aging, {}, { preserveScroll: true });
}

function formatMoney(value) {
    const n = Number(value || 0);
    return '\u20B5' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const bucketColors = {
    current: 'success',
    b31_60: 'info',
    b61_90: 'warning',
    b90_plus: 'danger',
};

function pdfUrl() {
    const params = new URLSearchParams();
    if (form.billing_type) params.set('billing_type', form.billing_type);
    if (form.sponsor_id) params.set('sponsor_id', form.sponsor_id);
    const qs = params.toString();
    return props.routes.pdf + (qs ? `?${qs}` : '');
}
</script>

<template>
    <AppLayout title="AR Aging">
        <div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-clock-dollar me-2"></i>Accounts Receivable Aging</h4>
            </div>
            <div class="d-flex gap-2">
                <a :href="pdfUrl()" class="btn btn-outline-danger btn-md">
                    <i class="ti ti-file-type-pdf me-1"></i>Export PDF
                </a>
            </div>
        </div>

        <!-- Bucket summary cards -->
        <div class="row g-3 mb-4">
            <div v-for="(bucket, key) in aging.buckets" :key="key" class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div :class="`avatar avatar-lg bg-soft-${bucketColors[key]} rounded me-3`">
                                <i :class="`ti ti-calendar-due fs-4 text-${bucketColors[key]}`"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ formatMoney(bucket.total) }}</h3>
                                <p class="text-muted mb-0">{{ bucket.label }} <span class="badge bg-light text-dark">{{ bucket.count }}</span></p>
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
                        <label class="form-label small">Billing Type</label>
                        <select v-model="form.billing_type" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">All Billing Types</option>
                            <option v-for="opt in billingTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Sponsor</label>
                        <select v-model="form.sponsor_id" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">All Sponsors</option>
                            <option v-for="s in sponsors" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearFilters"><i class="ti ti-x"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Aging table -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Open Invoices ({{ aging.grand_count }})</h6>
                <span class="fw-bold text-danger">Total: {{ formatMoney(aging.grand_total) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Patient</th>
                                <th>Sponsor</th>
                                <th>Billing Type</th>
                                <th>Due Date</th>
                                <th class="text-center">Days Overdue</th>
                                <th>Bucket</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in aging.rows" :key="row.id">
                                <td class="fw-medium">{{ row.invoice_number }}</td>
                                <td>
                                    <div class="fw-medium">{{ row.patient_name }}</div>
                                    <small class="text-muted">{{ row.patient_number }}</small>
                                </td>
                                <td>{{ row.sponsor_name ?? '—' }}</td>
                                <td>{{ row.billing_type ?? '—' }}</td>
                                <td>{{ row.due_date ?? '—' }}</td>
                                <td class="text-center">{{ row.days_overdue }}</td>
                                <td><span :class="`badge bg-soft-${bucketColors[row.bucket]}`">{{ aging.buckets[row.bucket]?.label }}</span></td>
                                <td class="text-end text-danger fw-bold">{{ formatMoney(row.balance) }}</td>
                            </tr>
                            <tr v-if="!aging.rows.length">
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="ti ti-circle-check fs-1 d-block mb-2 text-success"></i>
                                    No outstanding invoices.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
