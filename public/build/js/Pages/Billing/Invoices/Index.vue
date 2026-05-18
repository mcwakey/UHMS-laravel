<script setup>
/**
 * Billing → Invoices list (true Inertia, Phase B / B2).
 *
 * Mirrors the Visits/Index canonical template:
 * - AppLayout for chrome.
 * - Reactive filter form (search debounced) via router.get(..., { preserveState }).
 * - <Link> for cross-page nav, useForm() for row mutations (cancel, generate claim).
 * - All enums normalized to { value, label, color }.
 * - URLs and permission gates baked into the controller payload.
 */
import { reactive, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    invoices: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    billingTypeOptions: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
    csrf: { type: String, default: '' },
});

const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    billing_type: props.filters.billing_type ?? '',
});

function applyFilters() {
    router.get(props.routes.index, form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
function clearFilters() {
    form.search = '';
    form.status = '';
    form.billing_type = '';
    router.get(props.routes.index, {}, { preserveScroll: true });
}

let searchTimer = null;
watch(() => form.search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});

function cancelInvoice(invoice) {
    if (!invoice.urls.cancel) return;
    if (!window.confirm('Cancel this invoice?')) return;
    useForm({}).patch(invoice.urls.cancel, { preserveScroll: true });
}

function generateClaim(invoice) {
    if (!invoice.urls.generate_claim) return;
    if (invoice.has_insurance_provider) {
        useForm({
            invoice_id: invoice.id,
            insurance_provider_id: invoice.insurance_provider_id,
        }).post(invoice.urls.generate_claim, { preserveScroll: true });
    } else {
        router.get(invoice.urls.generate_claim);
    }
}

function formatMoney(value) {
    const n = Number(value || 0);
    return '\u20B5' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<template>
    <AppLayout title="Invoices">
        <!-- Page Header -->
        <div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-file-invoice me-2"></i>Invoices</h4>
            </div>
            <div class="d-flex gap-2">
                <Link v-if="can.create" :href="routes.create" class="btn btn-primary btn-md">
                    <i class="ti ti-plus me-1"></i>New Invoice
                </Link>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-soft-primary rounded me-3">
                                <i class="ti ti-file-invoice fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ Number(stats.total_invoices || 0).toLocaleString() }}</h3>
                                <p class="text-muted mb-0">Total Invoices</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-soft-warning rounded me-3">
                                <i class="ti ti-clock fs-4 text-warning"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ Number(stats.pending_invoices || 0).toLocaleString() }}</h3>
                                <p class="text-muted mb-0">Pending</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-soft-success rounded me-3">
                                <i class="ti ti-currency-dollar fs-4 text-success"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ formatMoney(stats.today_revenue) }}</h3>
                                <p class="text-muted mb-0">Today's Revenue</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-soft-danger rounded me-3">
                                <i class="ti ti-alert-triangle fs-4 text-danger"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ formatMoney(stats.outstanding_balance) }}</h3>
                                <p class="text-muted mb-0">Outstanding</p>
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
                        <input v-model="form.search" type="text" class="form-control form-control-sm" placeholder="Search invoice #, patient...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select v-model="form.status" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">All Statuses</option>
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Billing Type</label>
                        <select v-model="form.billing_type" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">All Billing Types</option>
                            <option v-for="opt in billingTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearFilters"><i class="ti ti-x"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Invoice Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Patient</th>
                                <th>Billing Type</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in invoices.data" :key="invoice.id">
                                <td>
                                    <Link :href="invoice.urls.show" class="fw-medium text-primary">
                                        {{ invoice.invoice_number }}
                                    </Link>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ invoice.patient?.full_name }}</div>
                                    <small class="text-muted">{{ invoice.patient?.patient_number }}</small>
                                </td>
                                <td>
                                    <span v-if="invoice.billing_type" :class="`badge bg-soft-${invoice.billing_type.color}`">
                                        {{ invoice.billing_type.label }}
                                    </span>
                                </td>
                                <td class="text-end fw-medium">{{ formatMoney(invoice.total_amount) }}</td>
                                <td class="text-end text-success">{{ formatMoney(invoice.amount_paid) }}</td>
                                <td class="text-end" :class="invoice.balance > 0 ? 'text-danger fw-bold' : ''">
                                    {{ formatMoney(invoice.balance) }}
                                </td>
                                <td>
                                    <span v-if="invoice.status" :class="`badge bg-${invoice.status.color}`">{{ invoice.status.label }}</span>
                                </td>
                                <td>{{ invoice.created_at_display }}</td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <Link class="dropdown-item" :href="invoice.urls.show">
                                                    <i class="ti ti-eye me-1"></i>View
                                                </Link>
                                            </li>
                                            <li v-if="invoice.urls.view_claim">
                                                <Link class="dropdown-item" :href="invoice.urls.view_claim">
                                                    <i class="ti ti-file-dollar me-1"></i>View Insurance Claim
                                                </Link>
                                            </li>
                                            <li v-if="invoice.can_create_claim">
                                                <button type="button" class="dropdown-item" @click="generateClaim(invoice)">
                                                    <i class="ti ti-file-plus me-1"></i>Generate Insurance Claim
                                                </button>
                                            </li>
                                            <li v-if="invoice.urls.cancel">
                                                <button type="button" class="dropdown-item text-danger" @click="cancelInvoice(invoice)">
                                                    <i class="ti ti-x me-1"></i>Cancel
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!invoices.data.length">
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-file-invoice fs-1 d-block mb-2"></i>
                                        No invoices found.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-if="invoices.links && invoices.links.length > 3" class="card-footer">
                <nav>
                    <ul class="pagination mb-0 justify-content-end">
                        <li v-for="(link, idx) in invoices.links" :key="idx" class="page-item"
                            :class="{ active: link.active, disabled: !link.url }">
                            <Link v-if="link.url" :href="link.url" class="page-link" preserve-scroll v-html="link.label" />
                            <span v-else class="page-link" v-html="link.label" />
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>
