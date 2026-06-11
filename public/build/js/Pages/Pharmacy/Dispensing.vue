<script setup>
/**
 * Pharmacy → Dispensing Queue (true Inertia, Phase B / B3).
 */
import { reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useTrans } from '../../composables/useTrans';

const props = defineProps({
    prescriptions: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
});

const { t } = useTrans();

const form = reactive({
    search: props.filters.search ?? '',
});

function applyFilters() {
    router.get(props.routes.index, form, { preserveState: true, preserveScroll: true, replace: true });
}
function clearFilters() {
    form.search = '';
    router.get(props.routes.index, {}, { preserveScroll: true });
}

let searchTimer = null;
watch(() => form.search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});
</script>

<template>
    <AppLayout :title="t('pharmacy.dispensing_queue')">
        <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-pill me-2"></i>{{ t('pharmacy.dispensing_queue') }}</h4>
            </div>
            <div>
                <Link :href="routes.history" class="btn btn-outline-secondary btn-md">
                    <i class="ti ti-history me-1"></i>{{ t('pharmacy.dispensing_history') }}
                </Link>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-3">
            <div class="col-md-2"><div class="card border-warning"><div class="card-body py-3 text-center">
                <h3 class="mb-0 text-warning">{{ stats.pending_prescriptions }}</h3>
                <small class="text-muted">{{ t('pharmacy.pending') }}</small>
            </div></div></div>
            <div class="col-md-2"><div class="card border-info"><div class="card-body py-3 text-center">
                <h3 class="mb-0 text-info">{{ stats.partially_dispensed }}</h3>
                <small class="text-muted">{{ t('pharmacy.partial') }}</small>
            </div></div></div>
            <div class="col-md-2"><div class="card border-success"><div class="card-body py-3 text-center">
                <h3 class="mb-0 text-success">{{ stats.dispensed_today }}</h3>
                <small class="text-muted">{{ t('pharmacy.dispensed_today') }}</small>
            </div></div></div>
            <div class="col-md-2"><div class="card border-danger"><div class="card-body py-3 text-center">
                <h3 class="mb-0 text-danger">{{ stats.low_stock_count }}</h3>
                <small class="text-muted">{{ t('pharmacy.low_stock') }}</small>
            </div></div></div>
            <div class="col-md-2"><div class="card border-orange"><div class="card-body py-3 text-center">
                <h3 class="mb-0 text-warning">{{ stats.expiring_soon_count }}</h3>
                <small class="text-muted">{{ t('pharmacy.expiring_soon') }}</small>
            </div></div></div>
            <div class="col-md-2"><div class="card border-primary"><div class="card-body py-3 text-center">
                <h3 class="mb-0 text-primary">{{ stats.total_drugs }}</h3>
                <small class="text-muted">{{ t('pharmacy.active_drugs') }}</small>
            </div></div></div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <form @submit.prevent="applyFilters" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <input v-model="form.search" type="text" class="form-control" :placeholder="t('pharmacy.search_placeholder')">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>{{ t('common.search') }}</button>
                        <button type="button" class="btn btn-outline-secondary btn-md" @click="clearFilters">{{ t('common.clear') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Prescriptions Queue -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ t('pharmacy.rx_number_short') }}</th>
                                <th>{{ t('common.patient') }}</th>
                                <th>{{ t('common.doctor') }}</th>
                                <th>{{ t('pharmacy.items') }}</th>
                                <th>{{ t('common.status') }}</th>
                                <th>{{ t('common.date') }}</th>
                                <th class="text-end">{{ t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="rx in prescriptions.data" :key="rx.id">
                                <td>
                                    <Link :href="rx.urls.show" class="fw-medium text-primary">{{ rx.prescription_number }}</Link>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ rx.patient?.full_name }}</div>
                                    <small class="text-muted">{{ rx.patient?.patient_number }}</small>
                                </td>
                                <td>{{ rx.doctor_name || '-' }}</td>
                                <td>
                                    <span class="badge bg-soft-primary">{{ t('pharmacy.items_count', { count: rx.items_count }) }}</span>
                                    <span v-if="rx.dispensed_count > 0" class="badge bg-soft-success">
                                        {{ t('pharmacy.dispensed_count', { count: rx.dispensed_count }) }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="rx.status" :class="`badge bg-${rx.status.color}`">{{ rx.status.label }}</span>
                                </td>
                                <td>
                                    <small>{{ rx.created_at_date }}</small><br>
                                    <small class="text-muted">{{ rx.created_at_time }}</small>
                                </td>
                                <td class="text-end">
                                    <Link :href="rx.urls.show" class="btn btn-sm btn-primary">
                                        <i class="ti ti-pill me-1"></i>{{ t('pharmacy.dispense') }}
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!prescriptions.data.length">
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="ti ti-pill fs-1 d-block mb-2"></i>
                                    {{ t('pharmacy.no_pending_prescriptions') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="prescriptions.links && prescriptions.links.length > 3" class="d-flex justify-content-end mt-3">
            <nav>
                <ul class="pagination mb-0">
                    <li v-for="(link, idx) in prescriptions.links" :key="idx" class="page-item"
                        :class="{ active: link.active, disabled: !link.url }">
                        <Link v-if="link.url" :href="link.url" class="page-link" preserve-scroll v-html="link.label" />
                        <span v-else class="page-link" v-html="link.label" />
                    </li>
                </ul>
            </nav>
        </div>
    </AppLayout>
</template>
