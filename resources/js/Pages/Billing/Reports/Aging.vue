<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    aging: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    payerTypeOptions: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    sponsors: { type: Array, default: () => [] },
    insuranceProviders: { type: Array, default: () => [] },
    corporateClients: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
});

const form = reactive({
    payer_type: props.filters.payer_type ?? '',
    status: props.filters.status ?? '',
    sponsor_id: props.filters.sponsor_id ?? '',
    insurance_provider_id: props.filters.insurance_provider_id ?? '',
    corporate_client_id: props.filters.corporate_client_id ?? '',
    as_of: props.filters.as_of ?? props.aging.as_of ?? '',
});

function applyFilters() {
    router.get(props.routes.aging, form, { preserveState: true, preserveScroll: true, replace: true });
}

function clearFilters() {
    form.payer_type = '';
    form.status = '';
    form.sponsor_id = '';
    form.insurance_provider_id = '';
    form.corporate_client_id = '';
    form.as_of = props.aging.as_of ?? '';
    router.get(props.routes.aging, {}, { preserveScroll: true });
}

function formatMoney(value) {
    const n = Number(value || 0);
    return '\u20B5' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const bucketColors = {
    not_due: 'success',
    b0_30: 'info',
    b31_60: 'info',
    b61_90: 'warning',
    b91_120: 'warning',
    b120_plus: 'danger',
};

const payerColors = {
    patient: 'success',
    insurance: 'primary',
    sponsor: 'info',
    corporate: 'dark',
};

function pdfUrl() {
    const params = new URLSearchParams();
    if (form.payer_type) params.set('payer_type', form.payer_type);
    if (form.status) params.set('status', form.status);
    if (form.sponsor_id) params.set('sponsor_id', form.sponsor_id);
    if (form.insurance_provider_id) params.set('insurance_provider_id', form.insurance_provider_id);
    if (form.corporate_client_id) params.set('corporate_client_id', form.corporate_client_id);
    if (form.as_of) params.set('as_of', form.as_of);
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

        <div class="row g-3 mb-4">
            <div v-for="(bucket, key) in aging.buckets" :key="key" class="col-xl-2 col-md-4 col-sm-6">
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

        <div class="row g-3 mb-4">
            <div v-for="(summary, key) in aging.payer_summary" :key="key" class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">{{ summary.label }}</div>
                                <div class="fw-bold">{{ formatMoney(summary.total) }}</div>
                            </div>
                            <span :class="`badge bg-soft-${payerColors[key] || 'secondary'}`">{{ summary.count }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body py-2">
                <form @submit.prevent="applyFilters" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small">Payer Type</label>
                        <select v-model="form.payer_type" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">All Payers</option>
                            <option v-for="opt in payerTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Status</label>
                        <select v-model="form.status" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">Open Statuses</option>
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Sponsor</label>
                        <select v-model="form.sponsor_id" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">All Sponsors</option>
                            <option v-for="s in sponsors" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Insurance</label>
                        <select v-model="form.insurance_provider_id" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">All Providers</option>
                            <option v-for="provider in insuranceProviders" :key="provider.id" :value="provider.id">{{ provider.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Corporate</label>
                        <select v-model="form.corporate_client_id" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">All Clients</option>
                            <option v-for="client in corporateClients" :key="client.id" :value="client.id">{{ client.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">As Of</label>
                        <input v-model="form.as_of" type="date" class="form-control form-control-sm" @change="applyFilters">
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearFilters"><i class="ti ti-x"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Open Receivables ({{ aging.grand_count }})</h6>
                <span class="fw-bold text-danger">Total: {{ formatMoney(aging.grand_total) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Patient</th>
                                <th>Payer</th>
                                <th>Due / Aging</th>
                                <th>Status</th>
                                <th class="text-center">Days</th>
                                <th>Bucket</th>
                                <th class="text-end">Allocated</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Adjustments</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in aging.rows" :key="row.id">
                                <td class="fw-medium"><a :href="row.invoice_url">{{ row.invoice_number }}</a></td>
                                <td>
                                    <div class="fw-medium">{{ row.patient_name }}</div>
                                    <small class="text-muted">{{ row.patient_number }}</small>
                                </td>
                                <td>
                                    <span :class="`badge bg-soft-${payerColors[row.payer_type] || 'secondary'}`">{{ row.payer_type }}</span>
                                    <div class="small text-muted">{{ row.payer_name }}</div>
                                </td>
                                <td>
                                    <div>{{ row.due_date ?? 'No due date' }}</div>
                                    <small class="text-muted">From {{ row.aging_start_date }}</small>
                                </td>
                                <td>{{ row.status.replaceAll('_', ' ') }}</td>
                                <td class="text-center">{{ row.days_overdue }}</td>
                                <td><span :class="`badge bg-soft-${bucketColors[row.bucket]}`">{{ aging.buckets[row.bucket]?.label }}</span></td>
                                <td class="text-end">{{ formatMoney(row.allocated_amount) }}</td>
                                <td class="text-end text-success">{{ formatMoney(row.paid_amount) }}</td>
                                <td class="text-end">{{ formatMoney(row.adjustment_amount) }}</td>
                                <td class="text-end text-danger fw-bold">{{ formatMoney(row.balance) }}</td>
                            </tr>
                            <tr v-if="!aging.rows.length">
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <i class="ti ti-circle-check fs-1 d-block mb-2 text-success"></i>
                                    No outstanding receivables.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
