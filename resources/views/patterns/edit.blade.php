@extends('layouts.app')
@section('title', 'Edit Medical Pattern')

@push('styles')
<style>
    .pattern-item { border: 1px solid #dee2e6; border-radius: 0.5rem; padding: 1rem; margin-bottom: 0.75rem; position: relative; }
    .pattern-item .remove-item { position: absolute; top: 0.5rem; right: 0.5rem; }
    .type-complaint { border-left: 3px solid #ffc107; }
    .type-history_of_presenting_complaint { border-left: 3px solid #fd7e14; }
    .type-examination { border-left: 3px solid #6c757d; }
    .type-diagnosis { border-left: 3px solid #0dcaf0; }
    .type-investigation { border-left: 3px solid #0dcaf0; }
    .type-treatment { border-left: 3px solid #198754; }
    .type-prescription_item { border-left: 3px solid #0d6efd; }
    .type-procedure { border-left: 3px solid #dc3545; }
    .type-task, .type-follow_up, .type-note { border-left: 3px solid #212529; }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="ti ti-template me-2"></i>Edit Medical Pattern</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.patterns.update', $pattern) }}" id="patternForm">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Pattern Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="e.g., Common Cold, Malaria Uncomplicated"
                                   value="{{ old('name', $pattern->name) }}">
                            @error('name')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Scope <span class="text-danger">*</span></label>
                            <select name="scope" class="form-select" required>
                                <option value="personal" {{ $pattern->doctor_id ? 'selected' : '' }}>Personal (My patterns)</option>
                                <option value="system" {{ !$pattern->doctor_id ? 'selected' : '' }}>System-Wide (All doctors)</option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <h5 class="fw-bold mb-3">Pattern Items</h5>

                    <div id="patternItems">
                        <!-- Items pre-populated by JS -->
                    </div>

                    <div class="d-flex gap-2 mb-4 flex-wrap">
                        <button type="button" class="btn btn-outline-warning btn-sm add-item-btn" data-type="complaint">
                            <i class="ti ti-plus me-1"></i>Complaint
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm add-item-btn" data-type="history_of_presenting_complaint">
                            <i class="ti ti-plus me-1"></i>HOPC
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm add-item-btn" data-type="examination">
                            <i class="ti ti-plus me-1"></i>Examination
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm add-item-btn" data-type="diagnosis">
                            <i class="ti ti-plus me-1"></i>Diagnosis
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm add-item-btn" data-type="investigation">
                            <i class="ti ti-plus me-1"></i>Investigation
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm add-item-btn" data-type="treatment">
                            <i class="ti ti-plus me-1"></i>Treatment
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm add-item-btn" data-type="prescription_item">
                            <i class="ti ti-plus me-1"></i>Prescription Item
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm add-item-btn" data-type="procedure">
                            <i class="ti ti-plus me-1"></i>Procedure
                        </button>
                        <button type="button" class="btn btn-outline-dark btn-sm add-item-btn" data-type="task">
                            <i class="ti ti-plus me-1"></i>Task
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm add-item-btn" data-type="note">
                            <i class="ti ti-plus me-1"></i>Note
                        </button>
                    </div>

                    <div id="noItemsMsg" class="text-center text-muted py-3 border rounded mb-3" style="display:none;">
                        <i class="ti ti-info-circle me-1"></i>Click the buttons above to add items to this pattern.
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.patterns.index') }}" class="btn btn-light">
                            <i class="ti ti-arrow-left me-1"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="ti ti-check me-1"></i>Update Pattern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Help Sidebar -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-help me-2"></i>How Patterns Work</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="fw-bold small text-warning"><i class="ti ti-message-report me-1"></i>Complaints</h6>
                    <p class="small text-muted mb-0">Patient symptoms that trigger pattern suggestions during consultations.</p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold small text-info"><i class="ti ti-report-medical me-1"></i>Diagnoses</h6>
                    <p class="small text-muted mb-0">Associated diagnoses with optional ICD-10 codes.</p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold small text-success"><i class="ti ti-vaccine me-1"></i>Treatments</h6>
                    <p class="small text-muted mb-0">Recommended treatments (medication, procedure, referral, advice).</p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold small text-primary"><i class="ti ti-prescription me-1"></i>Prescription Items</h6>
                    <p class="small text-muted mb-0">Pre-configured drug prescriptions with dosage and frequency.</p>
                </div>
                <hr>
                <div class="bg-light rounded p-2">
                    <small class="text-muted">
                        <strong>Tip:</strong> Remove items you no longer need and add new ones freely — the pattern will be fully replaced on save.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var itemIndex = 0;

var availableDrugs = @json($drugs);
var availableLabTests = @json($labTests);

function buildDrugOptions(selected) {
    selected = selected || '';
    var html = '<option value="">— Select drug —</option>';
    availableDrugs.forEach(function(d) {
        var label = d.name + (d.strength ? ' ' + d.strength : '') + (d.dosage_form ? ' (' + d.dosage_form + ')' : '');
        var val = d.name;
        html += '<option value="' + val.replace(/"/g, '&quot;') + '"' + (selected === val ? ' selected' : '') + '>' + label.replace(/"/g, '&quot;') + '</option>';
    });
    return html;
}

function buildLabTestOptions(selected) {
    selected = selected || '';
    var html = '<option value="">— Select investigation —</option>';
    availableLabTests.forEach(function(t) {
        html += '<option value="' + t.name.replace(/"/g, '&quot;') + '"' + (selected === t.name ? ' selected' : '') + '>' + t.name.replace(/"/g, '&quot;') + '</option>';
    });
    return html;
}

var templates = {
    complaint: function(idx) {
        return '<div class="pattern-item type-complaint" data-index="' + idx + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="ti ti-x"></i></button>' +
            '<input type="hidden" name="items[' + idx + '][type]" value="complaint">' +
            '<div class="d-flex align-items-center mb-2"><span class="badge bg-warning me-2">Complaint</span></div>' +
            '<div class="row g-2">' +
                '<div class="col-md-12"><label class="form-label small">Description <span class="text-danger">*</span></label>' +
                '<textarea name="items[' + idx + '][data][description]" class="form-control form-control-sm" rows="2" required placeholder="Complaint description..."></textarea></div>' +
                '<div class="col-md-6"><label class="form-label small">Duration</label>' +
                '<input type="text" name="items[' + idx + '][data][duration]" class="form-control form-control-sm" placeholder="e.g., 3 days"></div>' +
                '<div class="col-md-6"><label class="form-label small">Severity</label>' +
                '<select name="items[' + idx + '][data][severity]" class="form-select form-select-sm">' +
                '<option value="">-- Select --</option><option value="mild">Mild</option><option value="moderate">Moderate</option><option value="severe">Severe</option></select></div>' +
            '</div></div>';
    },
    history_of_presenting_complaint: function(idx) {
        return '<div class="pattern-item type-history_of_presenting_complaint" data-index="' + idx + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="ti ti-x"></i></button>' +
            '<input type="hidden" name="items[' + idx + '][type]" value="history_of_presenting_complaint">' +
            '<div class="d-flex align-items-center mb-2"><span class="badge bg-warning me-2">HOPC</span></div>' +
            '<div class="row g-2">' +
                '<div class="col-12"><label class="form-label small">Narrative <span class="text-danger">*</span></label>' +
                '<textarea name="items[' + idx + '][data][content]" class="form-control form-control-sm" rows="3" required placeholder="History of presenting complaint..."></textarea></div>' +
                '<div class="col-md-4"><input type="text" name="items[' + idx + '][data][onset]" class="form-control form-control-sm" placeholder="Onset"></div>' +
                '<div class="col-md-4"><input type="text" name="items[' + idx + '][data][duration]" class="form-control form-control-sm" placeholder="Duration"></div>' +
                '<div class="col-md-4"><input type="text" name="items[' + idx + '][data][severity]" class="form-control form-control-sm" placeholder="Severity"></div>' +
            '</div></div>';
    },
    examination: function(idx) {
        return '<div class="pattern-item type-examination" data-index="' + idx + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="ti ti-x"></i></button>' +
            '<input type="hidden" name="items[' + idx + '][type]" value="examination">' +
            '<div class="d-flex align-items-center mb-2"><span class="badge bg-secondary me-2">Examination</span></div>' +
            '<div class="row g-2">' +
                '<div class="col-12"><label class="form-label small">Findings <span class="text-danger">*</span></label>' +
                '<textarea name="items[' + idx + '][data][findings]" class="form-control form-control-sm" rows="3" required placeholder="Physical examination findings..."></textarea></div>' +
                '<div class="col-md-6"><textarea name="items[' + idx + '][data][general_examination]" class="form-control form-control-sm" rows="2" placeholder="General examination"></textarea></div>' +
                '<div class="col-md-6"><textarea name="items[' + idx + '][data][systemic_examination]" class="form-control form-control-sm" rows="2" placeholder="Systemic examination"></textarea></div>' +
            '</div></div>';
    },
    diagnosis: function(idx) {
        return '<div class="pattern-item type-diagnosis" data-index="' + idx + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="ti ti-x"></i></button>' +
            '<input type="hidden" name="items[' + idx + '][type]" value="diagnosis">' +
            '<div class="d-flex align-items-center mb-2"><span class="badge bg-info me-2">Diagnosis</span></div>' +
            '<div class="row g-2">' +
                '<div class="col-md-8"><label class="form-label small">Description <span class="text-danger">*</span></label>' +
                '<textarea name="items[' + idx + '][data][description]" class="form-control form-control-sm" rows="2" required placeholder="Diagnosis description..."></textarea></div>' +
                '<div class="col-md-4"><label class="form-label small">ICD-10 Code</label>' +
                '<input type="text" name="items[' + idx + '][data][icd_code]" class="form-control form-control-sm" placeholder="e.g., J06.9"></div>' +
                '<div class="col-md-6"><label class="form-label small">Type</label>' +
                '<select name="items[' + idx + '][data][type]" class="form-select form-select-sm">' +
                '<option value="provisional">Provisional</option><option value="final">Final</option></select></div>' +
                '<div class="col-md-6"><label class="form-label small">Notes</label>' +
                '<input type="text" name="items[' + idx + '][data][notes]" class="form-control form-control-sm" placeholder="Additional notes..."></div>' +
            '</div></div>';
    },
    treatment: function(idx) {
        return '<div class="pattern-item type-treatment" data-index="' + idx + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="ti ti-x"></i></button>' +
            '<input type="hidden" name="items[' + idx + '][type]" value="treatment">' +
            '<div class="d-flex align-items-center mb-2"><span class="badge bg-success me-2">Treatment</span></div>' +
            '<div class="row g-2">' +
                '<div class="col-md-4"><label class="form-label small">Type <span class="text-danger">*</span></label>' +
                '<select name="items[' + idx + '][data][type]" class="form-select form-select-sm" required>' +
                '<option value="medication">Medication</option><option value="procedure">Procedure</option><option value="referral">Referral</option><option value="advice">Advice</option></select></div>' +
                '<div class="col-md-8"><label class="form-label small">Description <span class="text-danger">*</span></label>' +
                '<textarea name="items[' + idx + '][data][description]" class="form-control form-control-sm" rows="2" required placeholder="Treatment description..."></textarea></div>' +
            '</div></div>';
    },
    investigation: function(idx) {
        return '<div class="pattern-item type-investigation" data-index="' + idx + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="ti ti-x"></i></button>' +
            '<input type="hidden" name="items[' + idx + '][type]" value="investigation">' +
            '<div class="d-flex align-items-center mb-2"><span class="badge bg-info me-2">Investigation Suggestion</span></div>' +
            '<div class="row g-2">' +
                '<div class="col-md-5"><label class="form-label small">Investigation <span class="text-danger">*</span></label>' +
                '<select name="items[' + idx + '][data][investigation_type]" class="form-select form-select-sm" required>' + buildLabTestOptions() + '</select></div>' +
                '<div class="col-md-5"><input type="text" name="items[' + idx + '][data][description]" class="form-control form-control-sm" placeholder="Clinical reason"></div>' +
                '<div class="col-md-2"><select name="items[' + idx + '][data][urgency]" class="form-select form-select-sm"><option value="routine">Routine</option><option value="urgent">Urgent</option><option value="emergency">Emergency</option></select></div>' +
            '</div></div>';
    },
    prescription_item: function(idx) {
        return '<div class="pattern-item type-prescription_item" data-index="' + idx + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="ti ti-x"></i></button>' +
            '<input type="hidden" name="items[' + idx + '][type]" value="prescription_item">' +
            '<div class="d-flex align-items-center mb-2"><span class="badge bg-primary me-2">Prescription Item</span></div>' +
            '<div class="row g-2">' +
                '<div class="col-md-4"><label class="form-label small">Drug Name <span class="text-danger">*</span></label>' +
                '<select name="items[' + idx + '][data][drug_name]" class="form-select form-select-sm" required>' + buildDrugOptions() + '</select></div>' +
                '<div class="col-md-2"><label class="form-label small">Dosage <span class="text-danger">*</span></label>' +
                '<input type="text" name="items[' + idx + '][data][dosage]" class="form-control form-control-sm" required placeholder="500mg"></div>' +
                '<div class="col-md-2"><label class="form-label small">Frequency</label>' +
                '<select name="items[' + idx + '][data][frequency]" class="form-select form-select-sm">' +
                '<option value="OD">OD</option><option value="BD">BD</option><option value="TDS" selected>TDS</option><option value="QDS">QDS</option><option value="STAT">STAT</option><option value="PRN">PRN</option></select></div>' +
                '<div class="col-md-2"><label class="form-label small">Duration</label>' +
                '<input type="text" name="items[' + idx + '][data][duration]" class="form-control form-control-sm" placeholder="5 days"></div>' +
                '<div class="col-md-2"><label class="form-label small">Qty</label>' +
                '<input type="number" name="items[' + idx + '][data][quantity]" class="form-control form-control-sm" min="1" value="1"></div>' +
                '<div class="col-md-3"><label class="form-label small">Route</label>' +
                '<select name="items[' + idx + '][data][route]" class="form-select form-select-sm">' +
                '<option value="oral">Oral</option><option value="IV">IV</option><option value="IM">IM</option><option value="SC">SC</option>' +
                '<option value="topical">Topical</option><option value="rectal">Rectal</option><option value="sublingual">Sublingual</option><option value="inhaled">Inhaled</option></select></div>' +
                '<div class="col-md-9"><label class="form-label small">Instructions</label>' +
                '<input type="text" name="items[' + idx + '][data][instructions]" class="form-control form-control-sm" placeholder="Special instructions..."></div>' +
            '</div></div>';
    },
    procedure: function(idx) {
        return '<div class="pattern-item type-procedure" data-index="' + idx + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="ti ti-x"></i></button>' +
            '<input type="hidden" name="items[' + idx + '][type]" value="procedure">' +
            '<div class="d-flex align-items-center mb-2"><span class="badge bg-danger me-2">Procedure Suggestion</span></div>' +
            '<textarea name="items[' + idx + '][data][description]" class="form-control form-control-sm" rows="2" required placeholder="Procedure / reason..."></textarea></div>';
    },
    task: function(idx) {
        return '<div class="pattern-item type-task" data-index="' + idx + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="ti ti-x"></i></button>' +
            '<input type="hidden" name="items[' + idx + '][type]" value="task">' +
            '<div class="d-flex align-items-center mb-2"><span class="badge bg-dark me-2">Task / Follow-up</span></div>' +
            '<input type="text" name="items[' + idx + '][data][title]" class="form-control form-control-sm mb-2" required placeholder="Task title">' +
            '<textarea name="items[' + idx + '][data][description]" class="form-control form-control-sm" rows="2" placeholder="Instructions"></textarea></div>';
    },
    note: function(idx) {
        return '<div class="pattern-item type-note" data-index="' + idx + '">' +
            '<button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="ti ti-x"></i></button>' +
            '<input type="hidden" name="items[' + idx + '][type]" value="note">' +
            '<div class="d-flex align-items-center mb-2"><span class="badge bg-secondary me-2">Clinical Note</span></div>' +
            '<textarea name="items[' + idx + '][data][content]" class="form-control form-control-sm" rows="2" required placeholder="Note / summary text..."></textarea></div>';
    },
    follow_up: function(idx) { return this.task(idx).replace('value="task"', 'value="follow_up"'); }
};

function bindRemove(el) {
    el.querySelector('.remove-item').addEventListener('click', function() {
        this.closest('.pattern-item').remove();
        updateSubmitBtn();
    });
}

function updateSubmitBtn() {
    var items = document.querySelectorAll('#patternItems .pattern-item');
    document.getElementById('submitBtn').disabled = items.length === 0;
    document.getElementById('noItemsMsg').style.display = items.length === 0 ? 'block' : 'none';
}

// Pre-populate existing items
var existingItems = @json($pattern->items->map(fn($i) => ['type' => $i->type, 'data' => $i->data]));

var container = document.getElementById('patternItems');
existingItems.forEach(function(item) {
    var type = item.type;
    if (!templates[type]) { type = 'note'; }
    var html = templates[type](itemIndex);
    container.insertAdjacentHTML('beforeend', html);
    var el = container.lastElementChild;
    // Fill in saved data values
    Object.entries(item.data || {}).forEach(function(pair) {
        var field = pair[0], value = pair[1];
        var input = el.querySelector('[name="items[' + itemIndex + '][data][' + field + ']"]');
        if (input) {
            if (input.tagName === 'SELECT') {
                // Find option with matching value
                var opt = input.querySelector('option[value="' + value + '"]');
                if (opt) { input.value = value; }
            } else {
                input.value = value !== null && value !== undefined ? value : '';
            }
        }
    });
    bindRemove(el);
    itemIndex++;
});

updateSubmitBtn();

document.querySelectorAll('.add-item-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var type = this.dataset.type;
        container.insertAdjacentHTML('beforeend', templates[type](itemIndex));
        bindRemove(container.lastElementChild);
        itemIndex++;
        updateSubmitBtn();
    });
});
</script>
@endpush
