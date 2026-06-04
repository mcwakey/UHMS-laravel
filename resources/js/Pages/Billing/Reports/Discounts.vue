<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    report: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
});

const form = reactive({
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    override: props.filters.override ?? '',
});

function applyFilters() {
    router.get(props.routes.discounts, form, { preserveState: true, preserveScroll: true, replace: true });
}

function clearFilters() {
    form.date_from = '';
    form.date_to = '';
    form.override = '';
    router.get(props.routes.discounts, {}, { preserveScroll: true });
}

function formatMoney(value) {
    const n = Number(value || 0);
    return '\u20B5' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<template>
    <AppLayout title="Discount Report">
        <div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-discount-2 me-2"></i>Discount Report</h4>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Events</p>
                        <h3 class="fw-bold mb-0">{{ report.summary.total_events }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Discount Added</p>
                        <h3 class="fw-bold mb-0 text-warning">{{ formatMoney(report.summary.total_discount_added) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Discount Removed</p>
                        <h3 class="fw-bold mb-0 text-info">{{ formatMoney(report.summary.total_discount_removed) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-1">Overrides</p>
                        <h3 class="fw-bold mb-0 text-danger">{{ report.summary.override_events }}</h3>
                    </div>
                </div>
            </div>
        </div>

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
                    <div class="col-md-3">
                        <label class="form-label small">Override</label>
                        <select v-model="form.override" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="1">Overrides only</option>
                            <option value="0">Non-overrides only</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearFilters"><i class="ti ti-x"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Patient</th>
                                <th>Item</th>
                                <th class="text-end">Old</th>
                                <th class="text-end">New</th>
                                <th>Risk</th>
                                <th>Reason</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="event in report.events.data" :key="event.id">
                                <td>{{ event.performed_at }}</td>
                                <td>
                                    <Link v-if="event.invoice_url" :href="event.invoice_url" class="fw-medium text-primary">
                                        {{ event.invoice_number }}
                                    </Link>
                                    <span v-else>{{ event.invoice_number ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <div>{{ event.patient_name }}</div>
                                    <small class="text-muted">{{ event.patient_number }}</small>
                                </td>
                                <td>{{ event.item }}</td>
                                <td class="text-end">{{ formatMoney(event.old_discount_amount) }}</td>
                                <td class="text-end">{{ formatMoney(event.new_discount_amount) }}</td>
                                <td>
                                    <span :class="event.is_override ? 'badge bg-danger' : 'badge bg-warning'">
                                        {{ event.is_override ? 'Override' : 'Manual' }}
                                    </span>
                                </td>
                                <td class="text-wrap" style="min-width: 220px;">{{ event.reason }}</td>
                                <td>{{ event.performed_by }}</td>
                            </tr>
                            <tr v-if="!report.events.data.length">
                                <td colspan="9" class="text-center py-4 text-muted">No discount events found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-if="report.events.links && report.events.links.length > 3" class="card-footer">
                <nav>
                    <ul class="pagination mb-0">
                        <li v-for="(link, idx) in report.events.links" :key="idx" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                            <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" />
                            <span v-else class="page-link" v-html="link.label"></span>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>
