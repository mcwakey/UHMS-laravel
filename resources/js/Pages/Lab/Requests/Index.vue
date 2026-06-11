<script setup>
/**
 * Lab → Investigation Requests list (true Inertia, Phase B / B6).
 */
import { reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useTrans } from '../../../composables/useTrans';

const props = defineProps({
    requests: { type: Object, required: true },
    stats: { type: Object, required: true },
    departments: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
});

const { t } = useTrans();

const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    department_id: props.filters.department_id ?? '',
    urgency: props.filters.urgency ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

function applyFilters() {
    router.get(props.routes.index, form, { preserveState: true, preserveScroll: true, replace: true });
}
function clearFilters() {
    Object.keys(form).forEach(k => { form[k] = ''; });
    router.get(props.routes.index, {}, { preserveScroll: true });
}

let searchTimer = null;
watch(() => form.search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});
</script>

<template>
    <AppLayout :title="t('lab.investigation_requests')">
        <div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-microscope me-2"></i>{{ t('lab.investigation_requests') }}</h4>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card border-warning"><div class="card-body py-3 text-center">
                    <h3 class="mb-0 text-warning">{{ stats.pending }}</h3>
                    <small class="text-muted">{{ t('lab.pending') }}</small>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card border-info"><div class="card-body py-3 text-center">
                    <h3 class="mb-0 text-info">{{ stats.processing }}</h3>
                    <small class="text-muted">{{ t('lab.processing') }}</small>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card border-success"><div class="card-body py-3 text-center">
                    <h3 class="mb-0 text-success">{{ stats.completed_today }}</h3>
                    <small class="text-muted">{{ t('lab.completed_today') }}</small>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary"><div class="card-body py-3 text-center">
                    <h3 class="mb-0 text-primary">{{ stats.total_tests }}</h3>
                    <small class="text-muted">{{ t('lab.active_tests') }}</small>
                </div></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <form @submit.prevent="applyFilters" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">{{ t('common.search') }}</label>
                        <input v-model="form.search" type="text" class="form-control" :placeholder="t('lab.search_placeholder')">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">{{ t('common.status') }}</label>
                        <select v-model="form.status" class="form-select" @change="applyFilters">
                            <option value="">{{ t('lab.all_status') }}</option>
                            <option value="pending">{{ t('lab.pending') }}</option>
                            <option value="processing">{{ t('lab.processing') }}</option>
                            <option value="completed">{{ t('lab.completed') }}</option>
                            <option value="cancelled">{{ t('lab.cancelled') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">{{ t('common.department') }}</label>
                        <select v-model="form.department_id" class="form-select" @change="applyFilters">
                            <option value="">{{ t('lab.all_departments') }}</option>
                            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">{{ t('lab.urgency') }}</label>
                        <select v-model="form.urgency" class="form-select" @change="applyFilters">
                            <option value="">{{ t('lab.all_urgency') }}</option>
                            <option value="routine">{{ t('lab.routine') }}</option>
                            <option value="urgent">{{ t('lab.urgent') }}</option>
                            <option value="emergency">{{ t('lab.emergency') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">{{ t('common.from') }}</label>
                        <input v-model="form.date_from" type="date" class="form-control" @change="applyFilters">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">{{ t('common.to') }}</label>
                        <input v-model="form.date_to" type="date" class="form-control" @change="applyFilters">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>{{ t('common.filter') }}</button>
                        <button type="button" class="btn btn-outline-secondary btn-md" @click="clearFilters">
                            <i class="ti ti-x me-1"></i>{{ t('common.clear') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ t('lab.request_number_short') }}</th>
                                <th>{{ t('common.patient') }}</th>
                                <th>{{ t('common.department') }}</th>
                                <th>{{ t('common.type') }}</th>
                                <th>{{ t('lab.urgency') }}</th>
                                <th>{{ t('common.status') }}</th>
                                <th>{{ t('lab.progress') }}</th>
                                <th>{{ t('common.date') }}</th>
                                <th class="text-end">{{ t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="req in requests.data" :key="req.id">
                                <td>
                                    <Link
                                        :href="req.status === 'pending' ? req.urls.show : req.urls.results"
                                        class="fw-medium text-primary"
                                    >
                                        {{ req.request_number }}
                                    </Link>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ req.patient?.full_name }}</div>
                                    <small class="text-muted">{{ req.patient?.patient_number }}</small>
                                </td>
                                <td>
                                    <span v-if="req.target_department_name" class="fw-medium">{{ req.target_department_name }}</span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td>
                                    <span v-if="req.result_type" :class="`badge bg-${req.result_type.color}`">
                                        <i :class="`ti ${req.result_type.icon} me-1`"></i>{{ req.result_type.label }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td><span :class="`badge bg-${req.urgency_color}`">{{ req.urgency_label }}</span></td>
                                <td><span :class="`badge bg-${req.status_color}`">{{ req.status_label }}</span></td>
                                <td>
                                    <div class="progress" style="height: 6px; width: 80px;">
                                        <div class="progress-bar bg-success" :style="{ width: req.completion_percentage + '%' }"></div>
                                    </div>
                                    <small class="text-muted">{{ req.completion_percentage }}%</small>
                                </td>
                                <td>
                                    <small>{{ req.created_at_date }}</small><br>
                                    <small class="text-muted">{{ req.created_at_time }}</small>
                                </td>
                                <td class="text-end">
                                    <Link
                                        v-if="req.status === 'pending'"
                                        :href="req.urls.show"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        <i class="ti ti-receipt me-1"></i>{{ t('lab.bill') }}
                                    </Link>
                                    <Link
                                        v-else
                                        :href="req.urls.results"
                                        class="btn btn-sm btn-outline-success"
                                    >
                                        <i class="ti ti-report-medical me-1"></i>{{ t('lab.results') }}
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="ti ti-microscope fs-1 d-block mb-2"></i>
                                    {{ t('lab.no_requests_found') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="requests.links && requests.links.length > 3" class="d-flex justify-content-end mt-3">
            <nav>
                <ul class="pagination mb-0">
                    <li v-for="(link, idx) in requests.links" :key="idx" class="page-item"
                        :class="{ active: link.active, disabled: !link.url }">
                        <Link v-if="link.url" :href="link.url" class="page-link" preserve-scroll v-html="link.label" />
                        <span v-else class="page-link" v-html="link.label" />
                    </li>
                </ul>
            </nav>
        </div>
    </AppLayout>
</template>
