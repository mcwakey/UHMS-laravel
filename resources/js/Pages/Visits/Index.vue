<script setup>
/**
 * Visits / OPD list page (true Inertia).
 *
 * Replaces resources/views/visits/index.blade.php — this is the canonical
 * Phase B template for future per-page migrations.
 *
 * Key patterns established here (reuse in future Phase B pages):
 * - AppLayout for chrome (sidebar + header injected once, shared via Inertia).
 * - Reactive filter form via router.get(..., { preserveState: true }).
 * - <Link> for cross-page nav (no full reload).
 * - Per-row mutating actions via useForm + form.patch() (no full reload).
 * - All permission gates received as a `can: {...}` prop.
 * - All enum metadata (labels, colors) baked into the props by the controller.
 */
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useTrans } from '../../composables/useTrans';

const props = defineProps({
    visits: { type: Object, required: true }, // Laravel paginator shape
    stats: { type: Object, required: true },
    doctors: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    visitTypeOptions: { type: Array, default: () => [] },
    insuranceProviderOptions: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const { t } = useTrans();

// Local reactive copy of the filter values (two-way bound to inputs).
const form = reactive({
    search: props.filters.search ?? '',
    visit_type: props.filters.visit_type ?? '',
    insurance_provider_id: props.filters.insurance_provider_id ?? '',
    date_range: props.filters.date_range ?? '',
});
const dateRangePicker = ref(null);
const dateRangeLabel = ref(t('visits.select_date_range'));

function applyFilters() {
    router.get(props.routes.index, form, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearFilters() {
    form.search = '';
    form.visit_type = '';
    form.insurance_provider_id = '';
    form.date_range = '';
    router.get(props.routes.index, {}, {
        preserveState: false,
        preserveScroll: true,
    });
}

// Debounce search-as-you-type (keep keystrokes responsive but avoid spam).
let searchTimer = null;
watch(() => form.search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});

onMounted(() => {
    initDateRangePicker();
});

onBeforeUnmount(() => {
    destroyDateRangePicker();
});

function parseDateRange(value) {
    if (!value || !window.moment) return null;

    const parts = String(value).split(/\s+(?:to|-)\s+/).filter(Boolean);
    if (!parts.length) return null;

    const start = window.moment(parts[0], 'YYYY-MM-DD', true);
    const end = window.moment(parts[1] || parts[0], 'YYYY-MM-DD', true);

    return start.isValid() && end.isValid() ? { start, end } : null;
}

function setDateRange(start, end) {
    form.date_range = `${start.format('YYYY-MM-DD')} to ${end.format('YYYY-MM-DD')}`;
    dateRangeLabel.value = `${start.format('D MMM YY')} - ${end.format('D MMM YY')}`;
}

function initDateRangePicker() {
    const $ = window.jQuery;
    const moment = window.moment;
    if (!dateRangePicker.value || !moment || !$?.fn?.daterangepicker) {
        const parsed = parseDateRange(form.date_range);
        if (parsed) {
            dateRangeLabel.value = `${parsed.start.format('D MMM YY')} - ${parsed.end.format('D MMM YY')}`;
        }
        return;
    }

    const parsed = parseDateRange(form.date_range);
    const start = parsed?.start ?? moment();
    const end = parsed?.end ?? moment();
    if (parsed) {
        dateRangeLabel.value = `${start.format('D MMM YY')} - ${end.format('D MMM YY')}`;
    }

    const $picker = $(dateRangePicker.value);
    $picker.daterangepicker({
        startDate: start,
        endDate: end,
        autoUpdateInput: false,
        opens: 'left',
        ranges: {
            [t('visits.today')]: [moment(), moment()],
            [t('visits.yesterday')]: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            [t('visits.last_7_days')]: [moment().subtract(6, 'days'), moment()],
            [t('visits.last_30_days')]: [moment().subtract(29, 'days'), moment()],
            [t('visits.this_month')]: [moment().startOf('month'), moment().endOf('month')],
            [t('visits.last_month')]: [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
        },
        locale: {
            cancelLabel: t('visits.clear'),
        },
    });

    $picker.on('apply.daterangepicker', (_event, picker) => {
        setDateRange(picker.startDate, picker.endDate);
        applyFilters();
    });

    $picker.on('cancel.daterangepicker', () => {
        form.date_range = '';
        dateRangeLabel.value = t('visits.select_date_range');
        applyFilters();
    });
}

function destroyDateRangePicker() {
    const $ = window.jQuery;
    if (!dateRangePicker.value || !$) return;

    const $picker = $(dateRangePicker.value);
    const instance = $picker.data('daterangepicker');
    $picker.off('.daterangepicker');
    instance?.remove();
}

function openDateRangePicker() {
    const $ = window.jQuery;
    if (!dateRangePicker.value || !$) return;

    $(dateRangePicker.value).trigger('click');
}

function filteredPageUrl(url) {
    if (!url) return '';

    const next = new URL(url, window.location.origin);

    Object.entries(form).forEach(([key, value]) => {
        if (value) {
            next.searchParams.set(key, value);
        } else {
            next.searchParams.delete(key);
        }
    });

    return `${next.pathname}${next.search}${next.hash}`;
}

function transitionVisit(visitId, nextStatusValue) {
    const url = props.routes.transition.replace('__ID__', String(visitId));
    const transitionForm = useForm({ status: nextStatusValue });
    transitionForm.patch(url, {
        preserveScroll: true,
        onError: () => {
            if (window.UhmsInertia?.toast) {
                window.UhmsInertia.toast(t('visits.transition_failed'), 'danger');
            }
        },
    });
}
</script>

<template>
    <AppLayout :title="t('visits.title')">
        <!-- Page Header -->
        <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0">{{ t('visits.title') }}</h4>
            </div>
            <div class="d-flex gap-2">
                <Link
                    v-if="can.queueView"
                    :href="routes.queueBoard"
                    class="btn btn-outline-info btn-md"
                >
                    <i class="ti ti-list-numbers me-1"></i>{{ t('visits.queue_board') }}
                </Link>
                <Link
                    v-if="can.create"
                    :href="routes.create"
                    class="btn btn-primary btn-md"
                >
                    <i class="ti ti-plus me-1"></i>{{ t('visits.new_visit') }}
                </Link>
            </div>
        </div>

        <!-- Visit Stats -->
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-primary border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">{{ t('visits.range_total') }}</p>
                        <h4 class="fw-bold mb-0">{{ stats.total }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-secondary border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">{{ t('visits.outpatients') }}</p>
                        <h4 class="fw-bold mb-0">{{ stats.outpatient }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-info border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">{{ t('visits.inpatients') }}</p>
                        <h4 class="fw-bold mb-0">{{ stats.inpatient }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-danger border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">{{ t('visits.emergency') }}</p>
                        <h4 class="fw-bold mb-0">{{ stats.emergency }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-warning border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">{{ t('visits.waiting_consulting') }}</p>
                        <h4 class="fw-bold mb-0">{{ stats.waiting_consulting }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-success border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">{{ t('visits.completed_cancelled') }}</p>
                        <h4 class="fw-bold mb-0">{{ stats.completed_cancelled }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form @submit.prevent="applyFilters">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">{{ t('common.search') }}</label>
                            <input
                                v-model="form.search"
                                type="text"
                                class="form-control"
                                :placeholder="t('visits.search_placeholder')"
                            >
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ t('visits.visit_type') }}</label>
                            <select v-model="form.visit_type" class="form-select" @change="applyFilters">
                                <option value="">{{ t('visits.all_types') }}</option>
                                <option
                                    v-for="opt in visitTypeOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >{{ opt.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ t('visits.active_insurance') }}</label>
                            <select v-model="form.insurance_provider_id" class="form-select" @change="applyFilters">
                                <option value="">{{ t('visits.all_insurance') }}</option>
                                <option
                                    v-for="opt in insuranceProviderOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >{{ opt.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ t('visits.date_range') }}</label>
                            <div
                                ref="dateRangePicker"
                                class="reportrange-picker d-flex align-items-center justify-content-between w-100"
                                role="button"
                                tabindex="0"
                                @keydown.enter.prevent="openDateRangePicker"
                                @keydown.space.prevent="openDateRangePicker"
                            >
                                <span class="d-flex align-items-center text-nowrap overflow-hidden">
                                    <i class="ti ti-calendar text-gray-5 fs-14 me-1"></i>
                                    <span class="reportrange-picker-field text-truncate">{{ dateRangeLabel }}</span>
                                </span>
                                <i class="ti ti-chevron-down text-gray-5 ms-2"></i>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-primary"><i class="ti ti-filter"></i></button>
                                <button type="button" class="btn btn-outline-secondary" @click="clearFilters">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Visit List -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ t('common.visit_number_short') }}</th>
                                <th>{{ t('common.patient') }}</th>
                                <th>{{ t('visits.active_insurance') }}</th>
                                <th>{{ t('common.age') }}</th>
                                <th>{{ t('common.type') }}</th>
                                <th>{{ t('common.priority') }}</th>
                                <th>{{ t('common.doctor') }}</th>
                                <th>{{ t('common.status') }}</th>
                                <th>{{ t('common.date') }}</th>
                                <th>{{ t('visits.duration') }}</th>
                                <th class="text-end">{{ t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="visit in visits.data" :key="visit.id">
                                <td>
                                    <Link
                                        :href="visit.urls.show"
                                        class="fw-medium text-primary"
                                    >{{ visit.visit_number }}</Link>
                                </td>
                                <td>
                                    <div>
                                        <Link
                                            :href="visit.urls.patient"
                                            class="fw-medium"
                                        >{{ visit.patient.full_name }}</Link>
                                        <br><small class="text-muted">{{ visit.patient.patient_number }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        :class="`badge bg-${visit.active_insurance.color}`"
                                        class="d-inline-flex align-items-center"
                                    >
                                        <i :class="`ti ti-${visit.active_insurance.is_cash ? 'cash' : 'shield-check'} me-1`"></i>
                                        {{ visit.active_insurance.label }}
                                    </span>
                                    <small
                                        v-if="visit.active_insurance.tier"
                                        class="text-muted d-block mt-1"
                                    >{{ visit.active_insurance.tier }}</small>
                                </td>
                                <td>{{ visit.age_display }}y</td>
                                <td>
                                    <span :class="`badge bg-${visit.visit_type.color}`">
                                        {{ visit.visit_type.label }}
                                    </span>
                                </td>
                                <td>
                                    <span :class="`badge bg-${visit.priority.color}`">{{ visit.priority.label }}</span>
                                    <span
                                        v-if="visit.triage_score"
                                        :class="`badge bg-${visit.triage_score.color} ms-1`"
                                    >{{ visit.triage_score.label }}</span>
                                </td>
                                <td>{{ visit.route_doctor_name || '—' }}</td>
                                <td>
                                    <span :class="`badge bg-${visit.status.color}`">{{ visit.status.label }}</span>
                                </td>
                                <td>{{ visit.visit_date_display }}</td>
                                <td>{{ visit.duration || '—' }}</td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button
                                            class="btn btn-sm btn-light"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                        >
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <Link
                                                    class="dropdown-item"
                                                    :href="visit.urls.show"
                                                ><i class="ti ti-eye me-2"></i>{{ t('visits.view_details') }}</Link>
                                            </li>
                                            <li v-if="can.edit">
                                                <Link
                                                    class="dropdown-item"
                                                    :href="visit.urls.edit"
                                                ><i class="ti ti-pencil me-2"></i>{{ t('visits.edit_visit') }}</Link>
                                            </li>
                                            <template v-if="visit.allowed_transitions.length">
                                                <li><hr class="dropdown-divider"></li>
                                                <li
                                                    v-for="next in visit.allowed_transitions"
                                                    :key="next.value"
                                                >
                                                    <button
                                                        type="button"
                                                        class="dropdown-item"
                                                        @click="transitionVisit(visit.id, next.value)"
                                                    >
                                                        <i class="ti ti-arrow-right me-2"></i>{{ next.label }}
                                                    </button>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!visits.data.length">
                                <td colspan="11" class="text-center text-muted py-4">
                                    <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>
                                    {{ t('visits.no_visits_found') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-if="visits.links && visits.links.length > 3" class="card-footer">
                <nav>
                    <ul class="pagination mb-0 justify-content-end">
                        <li
                            v-for="(link, idx) in visits.links"
                            :key="idx"
                            class="page-item"
                            :class="{ active: link.active, disabled: !link.url }"
                        >
                            <Link
                                v-if="link.url"
                                :href="filteredPageUrl(link.url)"
                                class="page-link"
                                preserve-scroll
                                preserve-state
                                v-html="link.label"
                            />
                            <span v-else class="page-link" v-html="link.label" />
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>
