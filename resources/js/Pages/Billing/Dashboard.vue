<script setup>
/**
 * Billing → Dashboard (Inertia). High-level financial KPIs, collection trend,
 * status breakdown, recent payments and top debtors.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useTrans } from '../../Composables/useTrans';

const { t } = useTrans();

const props = defineProps({
    metrics: { type: Object, required: true },
    routes: { type: Object, default: () => ({}) },
});

function formatMoney(value) {
    const n = Number(value || 0);
    return '\u20B5' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const trendMax = computed(() => {
    const amounts = (props.metrics.trend || []).map(t => Number(t.amount || 0));
    return Math.max(1, ...amounts);
});
</script>

<template>
    <AppLayout :title="t('billing.dashboard')">
        <div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-chart-bar me-2"></i>{{ t('billing.dashboard') }}</h4>
            </div>
            <div class="d-flex gap-2">
                <Link v-if="routes.counterSale" :href="routes.counterSale" class="btn btn-outline-primary btn-md">
                    <i class="ti ti-cash-register me-1"></i>{{ t('billing.counter_sale') }}
                </Link>
                <Link :href="routes.receive" class="btn btn-primary btn-md">
                    <i class="ti ti-cash me-1"></i>{{ t('billing.receive_payment') }}
                </Link>
                <Link :href="routes.aging" class="btn btn-outline-secondary btn-md">
                    <i class="ti ti-clock-dollar me-1"></i>{{ t('billing.ar_aging') }}
                </Link>
            </div>
        </div>

        <!-- KPI cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-soft-success rounded me-3">
                                <i class="ti ti-currency-dollar fs-4 text-success"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ formatMoney(metrics.collected_today) }}</h3>
                                <p class="text-muted mb-0">{{ t('billing.collected_today') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-soft-primary rounded me-3">
                                <i class="ti ti-calendar-stats fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ formatMoney(metrics.collected_month) }}</h3>
                                <p class="text-muted mb-0">{{ t('billing.collected_month') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-soft-info rounded me-3">
                                <i class="ti ti-file-invoice fs-4 text-info"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ formatMoney(metrics.billed_month) }}</h3>
                                <p class="text-muted mb-0">{{ t('billing.billed_month') }}</p>
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
                                <h3 class="fw-bold mb-0">{{ formatMoney(metrics.outstanding) }}</h3>
                                <p class="text-muted mb-0">{{ t('billing.outstanding') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Collection trend -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold"><i class="ti ti-trending-up me-1"></i>{{ t('billing.collection_trend_14_days') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-end gap-2" style="height: 200px;">
                            <div v-for="(t, idx) in metrics.trend" :key="idx"
                                 class="flex-fill d-flex flex-column align-items-center justify-content-end h-100"
                                 :title="formatMoney(t.amount)">
                                <div class="w-100 rounded-top bg-primary"
                                     :style="{ height: Math.max(2, (Number(t.amount || 0) / trendMax) * 100) + '%', opacity: 0.85 }"></div>
                                <small class="text-muted mt-1" style="font-size: 10px;">{{ t.date }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status breakdown -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold"><i class="ti ti-chart-pie me-1"></i>{{ t('billing.invoices_by_status') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li v-for="s in metrics.status_counts" :key="s.value"
                                class="list-group-item d-flex justify-content-between align-items-center">
                                <span><span :class="`badge bg-${s.color} me-2`">{{ s.count }}</span>{{ s.label }}</span>
                                <span class="fw-medium">{{ formatMoney(s.balance) }}</span>
                            </li>
                            <li v-if="!metrics.status_counts.length" class="list-group-item text-center text-muted">
                                {{ t('billing.no_data') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Recent payments -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="ti ti-receipt me-1"></i>{{ t('billing.recent_payments') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ t('billing.payment_number') }}</th>
                                        <th>{{ t('billing.patient') }}</th>
                                        <th>{{ t('billing.method') }}</th>
                                        <th class="text-end">{{ t('billing.amount') }}</th>
                                        <th>{{ t('billing.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(p, idx) in metrics.recent_payments" :key="idx">
                                        <td class="fw-medium">{{ p.payment_number }}</td>
                                        <td>{{ p.patient_name }}</td>
                                        <td>{{ p.method }}</td>
                                        <td class="text-end" :class="p.is_reversal ? 'text-danger fw-bold' : 'text-success'">
                                            {{ formatMoney(p.amount) }}
                                        </td>
                                        <td><small class="text-muted">{{ p.paid_at }}</small></td>
                                    </tr>
                                    <tr v-if="!metrics.recent_payments.length">
                                        <td colspan="5" class="text-center py-3 text-muted">{{ t('billing.no_payments_yet') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top debtors -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold"><i class="ti ti-user-dollar me-1"></i>{{ t('billing.top_outstanding_patients') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ t('billing.patient') }}</th>
                                        <th class="text-center">{{ t('billing.invoices_count') }}</th>
                                        <th class="text-end">{{ t('billing.balance') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(d, idx) in metrics.top_debtors" :key="idx">
                                        <td>
                                            <div class="fw-medium">{{ d.patient_name }}</div>
                                            <small class="text-muted">{{ d.patient_number }}</small>
                                        </td>
                                        <td class="text-center">{{ d.invoices }}</td>
                                        <td class="text-end text-danger fw-bold">{{ formatMoney(d.balance) }}</td>
                                    </tr>
                                    <tr v-if="!metrics.top_debtors.length">
                                        <td colspan="3" class="text-center py-3 text-muted">{{ t('billing.no_outstanding_balances') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
