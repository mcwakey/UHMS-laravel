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
import { reactive, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    visits: { type: Object, required: true }, // Laravel paginator shape
    stats: { type: Object, required: true },
    doctors: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    visitTypeOptions: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

// Local reactive copy of the filter values (two-way bound to inputs).
const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    visit_type: props.filters.visit_type ?? '',
    date_from: props.filters.date_from ?? '',
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
    form.visit_type = '';
    form.date_from = '';
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

function transitionVisit(visitId, nextStatusValue) {
    const url = props.routes.transition.replace('__ID__', String(visitId));
    const transitionForm = useForm({ status: nextStatusValue });
    transitionForm.patch(url, {
        preserveScroll: true,
        onError: () => {
            if (window.UhmsInertia?.toast) {
                window.UhmsInertia.toast('Status transition failed.', 'danger');
            }
        },
    });
}
</script>

<template>
    <AppLayout title="Visits / OPD">
        <!-- Page Header -->
        <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0">Visits / OPD</h4>
            </div>
            <div class="d-flex gap-2">
                <Link
                    v-if="can.queueView"
                    :href="routes.queueBoard"
                    class="btn btn-outline-info btn-md"
                >
                    <i class="ti ti-list-numbers me-1"></i>Queue Board
                </Link>
                <Link
                    v-if="can.create"
                    :href="routes.create"
                    class="btn btn-primary btn-md"
                >
                    <i class="ti ti-plus me-1"></i>New Visit
                </Link>
            </div>
        </div>

        <!-- Today's Stats -->
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-primary border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">Today's Total</p>
                        <h4 class="fw-bold mb-0">{{ stats.total }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-warning border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">Waiting</p>
                        <h4 class="fw-bold mb-0">{{ stats.waiting }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-info border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">Consulting</p>
                        <h4 class="fw-bold mb-0">{{ stats.consulting }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-success border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">Completed</p>
                        <h4 class="fw-bold mb-0">{{ stats.completed }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-danger border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">Emergency</p>
                        <h4 class="fw-bold mb-0">{{ stats.emergency }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-start border-secondary border-3">
                    <div class="card-body py-3 px-3">
                        <p class="text-muted mb-1 small">Cancelled</p>
                        <h4 class="fw-bold mb-0">{{ stats.cancelled }}</h4>
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
                            <label class="form-label">Search</label>
                            <input
                                v-model="form.search"
                                type="text"
                                class="form-control"
                                placeholder="Visit #, patient name, phone..."
                            >
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select v-model="form.status" class="form-select" @change="applyFilters">
                                <option value="">All Statuses</option>
                                <option
                                    v-for="opt in statusOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >{{ opt.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Visit Type</label>
                            <select v-model="form.visit_type" class="form-select" @change="applyFilters">
                                <option value="">All Types</option>
                                <option
                                    v-for="opt in visitTypeOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >{{ opt.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input
                                v-model="form.date_from"
                                type="date"
                                class="form-control"
                                @change="applyFilters"
                            >
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
                                <th>Visit #</th>
                                <th>Patient</th>
                                <th>Age</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Doctor</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Duration</th>
                                <th class="text-end">Actions</th>
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
                                <td>{{ visit.assigned_doctor_name || '—' }}</td>
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
                                                ><i class="ti ti-eye me-2"></i>View Details</Link>
                                            </li>
                                            <li v-if="can.edit">
                                                <Link
                                                    class="dropdown-item"
                                                    :href="visit.urls.edit"
                                                ><i class="ti ti-pencil me-2"></i>Edit Visit</Link>
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
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>
                                    No visits found
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
                                :href="link.url"
                                class="page-link"
                                preserve-scroll
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
