<template>
    <div class="complaint-selector">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small">Complaint</label>
                <input
                    v-model="query"
                    type="text"
                    class="form-control"
                    autocomplete="off"
                    placeholder="Search or type custom complaint"
                    @input="search"
                    @keydown.enter.prevent="addCustom"
                >
                <div v-if="results.length" class="list-group shadow-sm complaint-selector-results">
                    <button
                        v-for="item in results"
                        :key="item.id"
                        type="button"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                        @click="selectComplaint(item)"
                    >
                        <span>{{ item.name }}</span>
                        <small v-if="item.category" class="text-muted">{{ item.category }}</small>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Duration</label>
                <input v-model="draft.duration" type="text" class="form-control" placeholder="3">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Unit</label>
                <select v-model="draft.duration_unit" class="form-select">
                    <option value="">-</option>
                    <option value="minutes">Minutes</option>
                    <option value="hours">Hours</option>
                    <option value="days">Days</option>
                    <option value="weeks">Weeks</option>
                    <option value="months">Months</option>
                    <option value="years">Years</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Severity</label>
                <select v-model="draft.severity" class="form-select">
                    <option value="">-</option>
                    <option value="mild">Mild</option>
                    <option value="moderate">Moderate</option>
                    <option value="severe">Severe</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button type="button" class="btn btn-primary" :disabled="!canAdd" @click="addDraft">
                    <i class="ti ti-plus"></i>
                </button>
            </div>
            <div class="col-12">
                <textarea v-model="draft.notes" class="form-control" rows="2" placeholder="Notes"></textarea>
            </div>
        </div>

        <div v-if="model.length" class="mt-3 d-flex flex-column gap-2">
            <div v-for="(complaint, index) in model" :key="index" class="border rounded p-2 d-flex justify-content-between gap-2">
                <div>
                    <div class="fw-semibold">{{ complaint.description }}</div>
                    <small class="text-muted">
                        {{ [complaint.duration, complaint.duration_unit, complaint.severity].filter(Boolean).join(' ') }}
                    </small>
                    <small v-if="complaint.notes" class="text-muted d-block">{{ complaint.notes }}</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(index)">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    searchUrl: { type: String, required: true },
});

const emit = defineEmits(['update:modelValue', 'add', 'remove']);

const model = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
});

const query = ref('');
const results = ref([]);
const timer = ref(null);
const draft = reactive({
    complaint_catalogue_id: null,
    description: '',
    duration: '',
    duration_unit: '',
    severity: '',
    notes: '',
});

const canAdd = computed(() => draft.description.trim() !== '' || draft.complaint_catalogue_id !== null);

function search() {
    draft.description = query.value;
    draft.complaint_catalogue_id = null;
    clearTimeout(timer.value);
    if (query.value.trim().length < 2) {
        results.value = [];
        return;
    }

    timer.value = setTimeout(async () => {
        const url = new URL(props.searchUrl, window.location.origin);
        url.searchParams.set('q', query.value.trim());
        const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
        results.value = response.ok ? await response.json() : [];
    }, 250);
}

function selectComplaint(item) {
    draft.complaint_catalogue_id = item.id;
    draft.description = item.name;
    query.value = item.name;
    results.value = [];
}

function addCustom() {
    draft.description = query.value;
    addDraft();
}

function addDraft() {
    if (!canAdd.value) return;
    const item = {
        complaint_catalogue_id: draft.complaint_catalogue_id,
        description: draft.description.trim(),
        duration: draft.duration.trim(),
        duration_unit: draft.duration_unit,
        severity: draft.severity,
        notes: draft.notes.trim(),
    };
    model.value = [...model.value, item];
    emit('add', item);
    resetDraft();
}

function remove(index) {
    const removed = model.value[index];
    model.value = model.value.filter((_, itemIndex) => itemIndex !== index);
    emit('remove', removed);
}

function resetDraft() {
    query.value = '';
    results.value = [];
    draft.complaint_catalogue_id = null;
    draft.description = '';
    draft.duration = '';
    draft.duration_unit = '';
    draft.severity = '';
    draft.notes = '';
}
</script>

<style scoped>
.complaint-selector {
    position: relative;
}

.complaint-selector-results {
    position: absolute;
    z-index: 20;
    width: min(100%, 36rem);
    max-height: 16rem;
    overflow-y: auto;
}
</style>