<script setup>
/**
 * Billing → Corporate Sponsors (Inertia). CRUD for corporate sponsors /
 * payers via a create/edit modal, plus activate/deactivate toggle.
 */
import { reactive, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useConfirm } from '../../../Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
    sponsors: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const filterForm = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

function applyFilters() {
    router.get(props.routes.index, filterForm, { preserveState: true, preserveScroll: true, replace: true });
}
function clearFilters() {
    filterForm.search = '';
    filterForm.status = '';
    router.get(props.routes.index, {}, { preserveScroll: true });
}

function formatMoney(value) {
    const n = Number(value || 0);
    return '\u20B5' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// --- Create / edit modal ---
const showModal = ref(false);
const editing = ref(null);
const form = useForm({
    code: '',
    name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    credit_limit: '',
    is_active: true,
    notes: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showModal.value = true;
}
function openEdit(sponsor) {
    editing.value = sponsor;
    form.clearErrors();
    form.code = sponsor.code ?? '';
    form.name = sponsor.name ?? '';
    form.contact_person = sponsor.contact_person ?? '';
    form.email = sponsor.email ?? '';
    form.phone = sponsor.phone ?? '';
    form.address = sponsor.address ?? '';
    form.credit_limit = sponsor.credit_limit ?? '';
    form.is_active = sponsor.is_active;
    form.notes = sponsor.notes ?? '';
    showModal.value = true;
}
function closeModal() {
    showModal.value = false;
    editing.value = null;
}
function submit() {
    const opts = { preserveScroll: true, onSuccess: () => closeModal() };
    if (editing.value) {
        form.put(`${props.routes.index}/${editing.value.id}`, opts);
    } else {
        form.post(props.routes.store, opts);
    }
}

async function toggle(sponsor) {
    const ok = await confirm({
        title: sponsor.is_active ? 'Deactivate sponsor' : 'Activate sponsor',
        message: `${sponsor.is_active ? 'Deactivate' : 'Activate'} ${sponsor.name}?`,
        variant: sponsor.is_active ? 'danger' : 'primary',
        confirmLabel: sponsor.is_active ? 'Deactivate' : 'Activate',
    });
    if (!ok) return;
    router.patch(`${props.routes.index}/${sponsor.id}/toggle`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Corporate Sponsors">
        <div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0"><i class="ti ti-building-bank me-2"></i>Corporate Sponsors</h4>
            </div>
            <div class="d-flex gap-2">
                <button v-if="can.manage" type="button" class="btn btn-primary btn-md" @click="openCreate">
                    <i class="ti ti-plus me-1"></i>New Sponsor
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card uhms-stat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-soft-primary rounded me-3">
                                <i class="ti ti-building-bank fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ Number(stats.total || 0).toLocaleString() }}</h3>
                                <p class="text-muted mb-0">Total Sponsors</p>
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
                                <i class="ti ti-circle-check fs-4 text-success"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">{{ Number(stats.active || 0).toLocaleString() }}</h3>
                                <p class="text-muted mb-0">Active</p>
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
                    <div class="col-md-5">
                        <label class="form-label small">Search</label>
                        <input v-model="filterForm.search" type="text" class="form-control form-control-sm" placeholder="Name or code...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select v-model="filterForm.status" class="form-select form-select-sm" @change="applyFilters">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearFilters"><i class="ti ti-x"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th class="text-end">Credit Limit</th>
                                <th class="text-center">Invoices</th>
                                <th class="text-end">Outstanding</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in sponsors.data" :key="s.id">
                                <td class="fw-medium">{{ s.code }}</td>
                                <td>
                                    <div class="fw-medium">{{ s.name }}</div>
                                    <small v-if="s.email" class="text-muted">{{ s.email }}</small>
                                </td>
                                <td>
                                    <div>{{ s.contact_person ?? '—' }}</div>
                                    <small class="text-muted">{{ s.phone }}</small>
                                </td>
                                <td class="text-end">{{ s.credit_limit !== null ? formatMoney(s.credit_limit) : '—' }}</td>
                                <td class="text-center">{{ s.invoices_count }}</td>
                                <td class="text-end" :class="s.outstanding_balance > 0 ? 'text-danger fw-bold' : ''">
                                    {{ formatMoney(s.outstanding_balance) }}
                                </td>
                                <td>
                                    <span class="badge" :class="s.is_active ? 'bg-success' : 'bg-secondary'">
                                        {{ s.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div v-if="can.manage" class="dropdown">
                                        <button type="button" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item" @click="openEdit(s)">
                                                    <i class="ti ti-edit me-1"></i>Edit
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" :class="s.is_active ? 'text-danger' : 'text-success'" @click="toggle(s)">
                                                    <i :class="s.is_active ? 'ti ti-ban me-1' : 'ti ti-circle-check me-1'"></i>
                                                    {{ s.is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!sponsors.data.length">
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-building-bank fs-1 d-block mb-2"></i>
                                        No sponsors found.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-if="sponsors.links && sponsors.links.length > 3" class="card-footer">
                <nav>
                    <ul class="pagination mb-0 justify-content-end">
                        <li v-for="(link, idx) in sponsors.links" :key="idx" class="page-item"
                            :class="{ active: link.active, disabled: !link.url }">
                            <Link v-if="link.url" :href="link.url" class="page-link" preserve-scroll v-html="link.label" />
                            <span v-else class="page-link" v-html="link.label" />
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Create / edit modal -->
        <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editing ? 'Edit Sponsor' : 'New Sponsor' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small">Code</label>
                                <input v-model="form.code" type="text" class="form-control" :class="{ 'is-invalid': form.errors.code }" placeholder="Auto-generated if blank">
                                <div v-if="form.errors.code" class="invalid-feedback">{{ form.errors.code }}</div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small">Name <span class="text-danger">*</span></label>
                                <input v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.name }">
                                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Contact Person</label>
                                <input v-model="form.contact_person" type="text" class="form-control" :class="{ 'is-invalid': form.errors.contact_person }">
                                <div v-if="form.errors.contact_person" class="invalid-feedback">{{ form.errors.contact_person }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Email</label>
                                <input v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }">
                                <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Phone</label>
                                <input v-model="form.phone" type="text" class="form-control" :class="{ 'is-invalid': form.errors.phone }">
                                <div v-if="form.errors.phone" class="invalid-feedback">{{ form.errors.phone }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Credit Limit</label>
                                <div class="input-group">
                                    <span class="input-group-text">&#8373;</span>
                                    <input v-model="form.credit_limit" type="number" step="0.01" min="0" class="form-control" :class="{ 'is-invalid': form.errors.credit_limit }">
                                </div>
                                <div v-if="form.errors.credit_limit" class="text-danger small mt-1">{{ form.errors.credit_limit }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Address</label>
                                <input v-model="form.address" type="text" class="form-control" :class="{ 'is-invalid': form.errors.address }">
                                <div v-if="form.errors.address" class="invalid-feedback">{{ form.errors.address }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Notes</label>
                                <textarea v-model="form.notes" class="form-control" rows="2" :class="{ 'is-invalid': form.errors.notes }"></textarea>
                                <div v-if="form.errors.notes" class="invalid-feedback">{{ form.errors.notes }}</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input v-model="form.is_active" class="form-check-input" type="checkbox" id="sponsorActive">
                                    <label class="form-check-label" for="sponsorActive">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
                        <button type="button" class="btn btn-primary" :disabled="form.processing" @click="submit">
                            <i class="ti ti-check me-1"></i>{{ editing ? 'Save Changes' : 'Create' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
