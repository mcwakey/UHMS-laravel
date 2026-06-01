<script setup>
/**
 * Billing → Patient Statements search (Inertia). Search for a patient and
 * open their financial statement.
 */
import { reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    patients: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
});

const form = reactive({
    search: props.filters.search ?? '',
});

function applyFilters() {
    router.get(props.routes.index, form, { preserveState: true, preserveScroll: true, replace: true });
}

let searchTimer = null;
watch(() => form.search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});

function formatMoney(value) {
    const n = Number(value || 0);
    return '\u20B5' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<template>
    <AppLayout title="Patient Statements">
        <div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-file-text me-2"></i>Patient Statements</h4>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label small">Find Patient</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input v-model="form.search" type="text" class="form-control" placeholder="Search by name or patient number...">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient #</th>
                                <th>Name</th>
                                <th class="text-end">Outstanding</th>
                                <th class="text-center">Statement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in patients" :key="p.id">
                                <td class="fw-medium">{{ p.patient_number }}</td>
                                <td>{{ p.name }}</td>
                                <td class="text-end" :class="p.outstanding_balance > 0 ? 'text-danger fw-bold' : ''">
                                    {{ formatMoney(p.outstanding_balance) }}
                                </td>
                                <td class="text-center">
                                    <Link :href="p.url" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-file-text me-1"></i>View Statement
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!patients.length">
                                <td colspan="4" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-search fs-1 d-block mb-2"></i>
                                        {{ form.search ? 'No patients found.' : 'Search for a patient to view their statement.' }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
