<script setup>
/**
 * Admissions list (true Inertia, Phase B / B5).
 */
import { reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    admissions: { type: Object, required: true },
    total: { type: Number, default: 0 },
    stats: { type: Object, required: true },
    wards: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    ward_id: props.filters.ward_id ?? '',
});

function applyFilters() {
    router.get(props.routes.index, form, { preserveState: true, preserveScroll: true, replace: true });
}
function clearFilters() {
    form.search = '';
    form.status = '';
    form.ward_id = '';
    router.get(props.routes.index, {}, { preserveScroll: true });
}

let searchTimer = null;
watch(() => form.search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});
</script>

<template>
    <AppLayout title="Admissions">
        <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0">Admissions
                    <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">
                        Total: {{ total }}
                    </span>
                </h4>
            </div>
            <div class="text-end d-flex gap-2">
                <Link v-if="can.admit" :href="routes.create" class="btn btn-primary btn-md fs-13">
                    <i class="ti ti-plus me-1"></i>New Admission
                </Link>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md bg-primary bg-opacity-10 rounded me-3">
                                <i class="ti ti-bed fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ stats.total_admitted }}</h4>
                                <small class="text-muted">Currently Admitted</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md bg-success bg-opacity-10 rounded me-3">
                                <i class="ti ti-login fs-4 text-success"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ stats.admitted_today }}</h4>
                                <small class="text-muted">Admitted Today</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md bg-warning bg-opacity-10 rounded me-3">
                                <i class="ti ti-logout fs-4 text-warning"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ stats.discharged_today }}</h4>
                                <small class="text-muted">Discharged Today</small>
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
                    <div class="col-md-3">
                        <input v-model="form.search" type="text" class="form-control" placeholder="Search patient name, admission #...">
                    </div>
                    <div class="col-md-2">
                        <select v-model="form.status" class="form-select" @change="applyFilters">
                            <option value="">All Status</option>
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select v-model="form.ward_id" class="form-select" @change="applyFilters">
                            <option value="">All Wards</option>
                            <option v-for="w in wards" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-outline-primary btn-md"><i class="ti ti-filter me-1"></i>Filter</button>
                        <button type="button" class="btn btn-outline-secondary btn-md ms-1" @click="clearFilters">
                            <i class="ti ti-x me-1"></i>Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Admissions Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Admission #</th>
                                <th>Patient</th>
                                <th>Ward / Bed</th>
                                <th>Admitted On</th>
                                <th>Days</th>
                                <th>Admitted By</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="admission in admissions.data" :key="admission.id">
                                <td>
                                    <Link :href="admission.urls.show" class="fw-medium text-decoration-none">
                                        {{ admission.admission_number }}
                                    </Link>
                                </td>
                                <td>
                                    <div>
                                        <Link v-if="admission.urls.patient" :href="admission.urls.patient" class="text-decoration-none">
                                            {{ admission.patient?.full_name }}
                                        </Link>
                                        <span v-else>{{ admission.patient?.full_name }}</span>
                                    </div>
                                    <small class="text-muted">{{ admission.patient?.patient_number }}</small>
                                </td>
                                <td>
                                    <div>{{ admission.bed?.ward_name }}</div>
                                    <small class="text-muted">Bed: {{ admission.bed?.bed_number }}</small>
                                </td>
                                <td>{{ admission.admission_date_display }}</td>
                                <td><span class="badge badge-soft-secondary">{{ admission.length_of_stay }} day(s)</span></td>
                                <td>{{ admission.admitted_by_name || '—' }}</td>
                                <td>
                                    <span v-if="admission.status" :class="`badge badge-soft-${admission.status.color}`">
                                        {{ admission.status.label }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <Link class="dropdown-item" :href="admission.urls.show">
                                                    <i class="ti ti-eye me-1"></i>View Details
                                                </Link>
                                            </li>
                                            <li v-if="admission.urls.discharge">
                                                <Link class="dropdown-item" :href="admission.urls.discharge">
                                                    <i class="ti ti-logout me-1"></i>Discharge
                                                </Link>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!admissions.data.length">
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="ti ti-bed-off fs-1 d-block mb-2"></i>
                                    No admissions found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="admissions.links && admissions.links.length > 3" class="d-flex justify-content-end mt-3">
            <nav>
                <ul class="pagination mb-0">
                    <li v-for="(link, idx) in admissions.links" :key="idx" class="page-item"
                        :class="{ active: link.active, disabled: !link.url }">
                        <Link v-if="link.url" :href="link.url" class="page-link" preserve-scroll v-html="link.label" />
                        <span v-else class="page-link" v-html="link.label" />
                    </li>
                </ul>
            </nav>
        </div>
    </AppLayout>
</template>
