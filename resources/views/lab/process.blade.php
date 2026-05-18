@extends('layouts.app')
@section('title', 'Investigation: ' . $request->request_number)

@section('content')
@php $resultType = $request->result_type ?? \App\Enums\ResultType::PARAMETERS; @endphp
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <a href="{{ route('admin.lab.requests.index') }}" class="text-muted me-2"><i class="ti ti-arrow-left"></i></a>
            {{ $request->request_number }}
            <span class="badge bg-{{ $resultType->color() }} ms-2"><i class="ti {{ $resultType->icon() }} me-1"></i>{{ $resultType->label() }}</span>
        </h4>
        @if($request->targetDepartment)<small class="text-muted">Department: <strong>{{ $request->targetDepartment->name }}</strong></small>@endif
    </div>
    <div class="d-flex gap-2">
        @if(!in_array($request->status, ['completed', 'cancelled']))
        <form method="POST" action="{{ route('admin.lab.requests.cancel', $request) }}" onsubmit="return confirm('Cancel this lab request?')">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-outline-danger btn-md"><i class="ti ti-x me-1"></i>Cancel</button>
        </form>
        @endif
    </div>
</div>

@if($request->status === 'pending')
{{-- ===================== SELECTIVE ACCEPTANCE ===================== --}}
<div class="card mb-3 border-primary">
    <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="ti ti-list-check me-1"></i>Select Items to Accept &amp; Bill</h6>
        <small class="text-muted"><span id="selCount">0</span> of {{ $request->items->where('status','pending')->count() }} selected</small>
    </div>
    <div class="card-body">
        <form id="acceptSelectedForm" method="POST" action="{{ route('admin.lab.requests.accept-selected', $request) }}">
            @csrf
            <div id="acceptSelectedErrors" class="alert alert-danger d-none small py-2 mb-2"></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-2">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px;"><input type="checkbox" id="selectAllItems" class="form-check-input"></th>
                            <th>Item</th>
                            <th>Status</th>
                            <th class="text-end">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($request->items as $it)
                        @php
                            $price = $it->service?->price ?? null;
                        @endphp
                        <tr class="{{ $it->status !== 'pending' ? 'text-muted' : '' }}">
                            <td>
                                @if($it->status === 'pending')
                                    <input type="checkbox" name="item_ids[]" value="{{ $it->id }}" class="form-check-input acceptSelectedCb">
                                @else
                                    <i class="ti ti-lock text-muted"></i>
                                @endif
                            </td>
                            <td>
                                <span class="fw-medium">{{ $it->display_name }}</span>
                                @if($it->service)<small class="text-muted d-block">{{ $it->service->code ?? '' }}</small>@endif
                            </td>
                            <td><span class="badge bg-{{ $it->status_color }}">{{ ucfirst($it->status) }}</span></td>
                            <td class="text-end">{{ $price !== null ? number_format($price, 2) : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted"><i class="ti ti-info-circle me-1"></i>Only selected items are accepted &amp; invoiced. Unselected items remain pending.</small>
                <button type="submit" id="acceptSelectedBtn" class="btn btn-success" disabled>
                    <i class="ti ti-check me-1"></i>Accept Selected &amp; Generate Invoice
                </button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
(function() {
    const form = document.getElementById('acceptSelectedForm');
    if (!form) return;
    const cbs   = form.querySelectorAll('.acceptSelectedCb');
    const all   = document.getElementById('selectAllItems');
    const cnt   = document.getElementById('selCount');
    const btn   = document.getElementById('acceptSelectedBtn');
    const errs  = document.getElementById('acceptSelectedErrors');
    const sync = () => {
        const checked = [...cbs].filter(c => c.checked).length;
        cnt.textContent = checked;
        btn.disabled = checked === 0;
        all.checked = checked === cbs.length && cbs.length > 0;
        all.indeterminate = checked > 0 && checked < cbs.length;
    };
    all.addEventListener('change', () => { cbs.forEach(c => c.checked = all.checked); sync(); });
    cbs.forEach(c => c.addEventListener('change', sync));
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errs.classList.add('d-none');
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing…';
        try {
            const fd = new FormData(form);
            const r = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            });
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
                errs.textContent = data.error || ('Error ' + r.status);
                errs.classList.remove('d-none');
                btn.disabled = false; btn.innerHTML = '<i class="ti ti-check me-1"></i>Accept Selected &amp; Generate Invoice';
                return;
            }
            const target = data.redirect || window.location.href;
            if (window.UhmsInertia && data.redirect) {
                window.UhmsInertia.visit(target, { preserveScroll: true });
            } else {
                window.location.href = target;
            }
        } catch (err) {
            errs.textContent = err.message;
            errs.classList.remove('d-none');
            btn.disabled = false; btn.innerHTML = '<i class="ti ti-check me-1"></i>Accept Selected &amp; Generate Invoice';
        }
    });
})();
</script>
@endpush
@endif

<!-- Request Info -->
<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-info-circle me-1"></i>Request Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Patient</small>
                        <span class="fw-medium">{{ $request->patient->full_name }}</span>
                        <small class="text-muted d-block">{{ $request->patient->patient_number }} &middot; {{ $request->patient->age }}y &middot; {{ $request->patient->gender->value }}</small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-{{ $request->status_color }} px-3 py-2">{{ $request->status_label }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Urgency</small>
                        <span class="badge bg-{{ $request->urgency_color }} px-3 py-2">{{ ucfirst($request->urgency) }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Requested By</small>
                        <span>{{ $request->requestedBy->name ?? '-' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Requesting Dept</small>
                        <span>{{ $request->department->name ?? '-' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Date</small>
                        <span>{{ $request->created_at->format('d M Y H:i') }}</span>
                    </div>
                    @if($request->clinical_info)
                    <div class="col-12">
                        <small class="text-muted d-block">Clinical Information</small>
                        <p class="mb-0">{{ $request->clinical_info }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-chart-bar me-1"></i>Progress</h6>
            </div>
            <div class="card-body text-center">
                <h1 class="mb-1 {{ $request->completion_percentage === 100 ? 'text-success' : 'text-primary' }}">{{ $request->completion_percentage }}%</h1>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: {{ $request->completion_percentage }}%"></div>
                </div>
                <small class="text-muted">
                    {{ $request->items->where('status', 'completed')->count() }} of {{ $request->items->count() }} tests completed
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Investigation Items & Results -->
@if($resultType === \App\Enums\ResultType::PARAMETERS)
{{-- ======================= PARAMETERS (Lab Tests) ======================= --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="ti ti-flask me-1"></i>Test Items & Results</h6>
        @if($request->status === 'processing')
        @can('lab.results.create')
        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#batchResultForm">
            <i class="ti ti-edit me-1"></i>Batch Enter Results
        </button>
        @endcan
        @endif
    </div>
    <div class="card-body">
        {{-- Batch Entry Form --}}
        @if($request->status === 'processing')
        @can('lab.results.create')
        <div class="collapse mb-3" id="batchResultForm">
            <div class="card card-body bg-light">
                <form method="POST" action="{{ route('admin.lab.results.batch', $request) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Normal Range</th>
                                    <th>Result Value</th>
                                    <th>Abnormal?</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($request->items as $item)
                                @if($item->status !== 'completed')
                                <tr>
                                    <td class="fw-medium">{{ $item->labTest->name ?? $item->name }} <small class="text-muted">({{ $item->labTest->code ?? '' }})</small></td>
                                    <td>
                                        @if($item->labTest?->criteria?->isNotEmpty())
                                            @foreach($item->labTest->criteria as $criterion)
                                                <div><small><strong>{{ $criterion->name }}:</strong> {{ $criterion->normal_range ?? '-' }} {{ $criterion->unit ?? '' }}</small></div>
                                            @endforeach
                                        @else
                                            <small>{{ $item->labTest->normal_range ?? '-' }} {{ $item->labTest->unit ?? '' }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="text" name="results[{{ $item->id }}][result_value]" class="form-control form-control-sm" placeholder="Enter result">
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input type="hidden" name="results[{{ $item->id }}][is_abnormal]" value="0">
                                            <input type="checkbox" name="results[{{ $item->id }}][is_abnormal]" value="1" class="form-check-input">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="results[{{ $item->id }}][remarks]" class="form-control form-control-sm" placeholder="Optional">
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save Results</button>
                    </div>
                </form>
            </div>
        </div>
        @endcan
        @endif

        {{-- Items List --}}
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Test Name</th>
                        <th>Category</th>
                        <th>Normal Range</th>
                        <th>Status</th>
                        <th>Result</th>
                        <th>Performed By</th>
                        <th>Verified</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($request->items as $index => $item)
                    <tr class="{{ $item->result && $item->result->is_abnormal ? 'table-danger' : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td class="fw-medium">{{ $item->labTest->name ?? $item->name }} <small class="text-muted">({{ $item->labTest->code ?? '' }})</small></td>
                        <td>{{ $item->labTest?->category?->name ?? '-' }}</td>
                        <td>
                            @if($item->labTest?->criteria?->isNotEmpty())
                                @foreach($item->labTest->criteria as $criterion)
                                    <div><small><strong>{{ $criterion->name }}:</strong> {{ $criterion->normal_range ?? '-' }} {{ $criterion->unit ?? '' }}</small></div>
                                @endforeach
                            @elseif($item->service_id)
                                <small class="text-muted">See criteria form</small>
                            @else
                                <small>{{ $item->labTest?->normal_range ?? '-' }} {{ $item->labTest?->unit ?? '' }}</small>
                            @endif
                        </td>
                        <td><span class="badge bg-{{ $item->status_color }}">{{ ucfirst($item->status) }}</span></td>
                        <td>
                            @if($item->result)
                                <span class="{{ $item->result->is_abnormal ? 'text-danger fw-bold' : '' }}">{{ $item->result->result_value }}</span>
                                @if($item->result->is_abnormal)<i class="ti ti-alert-triangle text-danger ms-1"></i>@endif
                                @if($item->result->remarks)<br><small class="text-muted">{{ $item->result->remarks }}</small>@endif
                            @else <span class="text-muted">-</span> @endif
                        </td>
                        <td>
                            @if($item->result)
                                <small>{{ $item->result->performedBy->name ?? '-' }}</small><br>
                                <small class="text-muted">{{ $item->result->performed_at?->format('d M H:i') }}</small>
                            @else - @endif
                        </td>
                        <td>
                            @if($item->result && $item->result->is_verified)
                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>Verified</span>
                                <br><small class="text-muted">{{ $item->result->verifiedBy->name ?? '' }}</small>
                            @elseif($item->result) <span class="badge bg-warning">Unverified</span>
                            @else - @endif
                        </td>
                        <td class="text-end">
                            @if($item->result)
                            <button type="button" class="btn btn-sm btn-outline-info viewResultBtn"
                                data-url="{{ route('admin.lab.results.view', $item) }}"
                                title="View Result"><i class="ti ti-eye"></i></button>
                            @endif
                            @if($item->result && !$item->result->is_verified)
                            @can('lab.results.create')
                            <form method="POST" action="{{ route('admin.lab.results.verify', $item->result) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Verify"><i class="ti ti-check"></i></button>
                            </form>
                            @endcan
                            @endif
                            @if($item->result && $item->result->is_verified)
                            <a data-no-inertia href="{{ route('admin.lab.results.print', $item) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print"><i class="ti ti-printer"></i></a>
                            @endif
                            @if(in_array($request->status, ['processing']) && in_array($item->status, ['accepted','processing']) && !$item->result)
                            @can('lab.results.create')
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#resultModal-{{ $item->id }}" title="Enter Result"><i class="ti ti-edit"></i></button>
                            @endcan
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Per-item modals (parameters) --}}
@foreach($request->items as $item)
@if(in_array($request->status, ['processing']) && in_array($item->status, ['accepted','processing']) && !$item->result)
<div class="modal fade" id="resultModal-{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.lab.results.store', $item) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="result_type" value="parameters">
            <div class="modal-header">
                <h5 class="modal-title">Result: {{ $item->labTest->name ?? $item->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($item->labTest?->criteria?->isNotEmpty())
                <div class="alert alert-info py-2 mb-3">
                    @foreach($item->labTest->criteria as $criterion)
                        <div><small><strong>{{ $criterion->name }}:</strong> {{ $criterion->normal_range ?? '-' }} {{ $criterion->unit ?? '' }}</small></div>
                    @endforeach
                </div>
                @elseif(($item->labTest->normal_range ?? null))
                <div class="alert alert-info py-2 mb-3">
                    <small><strong>Normal Range:</strong> {{ $item->labTest->normal_range }} {{ $item->labTest->unit ?? '' }}</small>
                </div>
                @endif
                @php
                    $serviceCriteria = $item->service?->investigationCriteria?->where('is_active', true) ?? collect();
                    $serviceHeaders  = $item->service?->investigationHeaders?->where('is_active', true) ?? collect();
                @endphp
                @if($serviceCriteria->isNotEmpty())
                    {{-- Investigation Catalogue criteria-based result entry --}}
                    <div class="border rounded p-2 mb-3 bg-light">
                        <div class="small fw-bold text-muted text-uppercase mb-2">Result Criteria</div>
                        @foreach($serviceHeaders as $h)
                            @php $hCriteria = $serviceCriteria->where('header_id', $h->id); @endphp
                            @if($hCriteria->isNotEmpty())
                                <div class="mb-3">
                                    <h6 class="small fw-bold border-bottom pb-1 mb-2">{{ $h->name }}</h6>
                                    @foreach($hCriteria as $c)
                                        @include('lab._criterion_input', ['c' => $c])
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                        @php $unsorted = $serviceCriteria->whereNull('header_id'); @endphp
                        @if($unsorted->isNotEmpty())
                            <div class="mb-2">
                                @foreach($unsorted as $c)
                                    @include('lab._criterion_input', ['c' => $c])
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label">Result Value <span class="text-danger">*</span></label>
                    <textarea name="result_value" class="form-control" rows="2" required placeholder="Enter test result..."></textarea>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="hidden" name="is_abnormal" value="0">
                        <input type="checkbox" name="is_abnormal" value="1" class="form-check-input" id="abnormal-{{ $item->id }}">
                        <label class="form-check-label" for="abnormal-{{ $item->id }}">Mark as Abnormal</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
                </div>
                @include('lab._consumables', ['item' => $item])
                <hr class="my-3">
                <div class="mb-2">
                    <label class="form-label small fw-medium"><i class="ti ti-paperclip me-1"></i>Attach image / document <span class="text-muted">(optional)</span></label>
                    <input type="file" name="result_file" class="form-control form-control-sm" accept="image/*,.pdf,.doc,.docx">
                    <div class="form-text">Image, PDF or Word — max 20MB. Always available regardless of result type.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Result</button>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach

@elseif($resultType === \App\Enums\ResultType::RICHTEXT)
{{-- ======================= RICHTEXT (Scan/Radiology Report) ======================= --}}
<div class="card">
    <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-align-left me-1"></i>Investigation Items &amp; Reports</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Investigation</th><th>Status</th><th>Report Preview</th><th>Verified</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($request->items as $idx => $item)
                    <tr class="{{ $item->result?->is_abnormal ? 'table-warning' : '' }}">
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-medium">{{ $item->name ?? $item->labTest?->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $item->status_color }}">{{ ucfirst($item->status) }}</span></td>
                        <td>
                            @if($item->result?->result_text)
                                <small class="text-muted">{{ Str::limit(strip_tags($item->result->result_text), 80) }}</small>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td>
                            @if($item->result?->is_verified)
                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>Verified</span>
                            @elseif($item->result) <span class="badge bg-warning">Unverified</span>
                            @else — @endif
                        </td>
                        <td class="text-end">
                            @if($item->result && !$item->result->is_verified)
                            @can('lab.results.create')
                            <form method="POST" action="{{ route('admin.lab.results.verify', $item->result) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-success"><i class="ti ti-check"></i> Verify</button>
                            </form>
                            @endcan
                            @endif
                            @if($request->status === 'processing' && $item->status !== 'completed')
                            @can('lab.results.create')
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#richtextModal-{{ $item->id }}">
                                <i class="ti ti-edit me-1"></i>{{ $item->result ? 'Update' : 'Write' }} Report
                            </button>
                            @endcan
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($request->items as $item)
@if($request->status === 'processing' && $item->status !== 'completed')
<div class="modal fade" id="richtextModal-{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.lab.results.store', $item) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="result_type" value="richtext">
            <div class="modal-header">
                <h5 class="modal-title">Report: {{ $item->name ?? $item->labTest?->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">Findings / Report <span class="text-danger">*</span></label>
                    <textarea name="result_text" class="form-control" rows="12" required
                        placeholder="Enter scan findings, impressions, and conclusions...">{{ $item->result?->result_text }}</textarea>
                </div>
                <div class="row g-2">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="hidden" name="is_abnormal" value="0">
                            <input type="checkbox" name="is_abnormal" value="1" class="form-check-input" id="abn-rt-{{ $item->id }}" {{ $item->result?->is_abnormal ? 'checked' : '' }}>
                            <label class="form-check-label text-danger fw-medium" for="abn-rt-{{ $item->id }}">Abnormal / Significant Finding</label>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Optional brief remark" value="{{ $item->result?->remarks }}">
                </div>
                <hr class="my-3">
                <div class="mb-2">
                    <label class="form-label small fw-medium"><i class="ti ti-paperclip me-1"></i>Attach image / document <span class="text-muted">(optional)</span></label>
                    <input type="file" name="result_file" class="form-control form-control-sm" accept="image/*,.pdf,.doc,.docx">
                    <div class="form-text">Image, PDF or Word — max 20MB. Always available regardless of result type.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-send me-1"></i>Submit Report</button>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach

@else
{{-- ======================= IMAGE / DOCUMENT ======================= --}}
<div class="card">
    <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti {{ $resultType->icon() }} me-1"></i>Investigation Items &amp; Files</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Investigation</th><th>Status</th><th>File</th><th>Verified</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($request->items as $idx => $item)
                    <tr class="{{ $item->result?->is_abnormal ? 'table-warning' : '' }}">
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-medium">{{ $item->name ?? $item->labTest?->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $item->status_color }}">{{ ucfirst($item->status) }}</span></td>
                        <td>
                            @if($item->result?->result_file)
                                <a href="{{ Storage::url($item->result->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-download me-1"></i>{{ $item->result->result_file_name ?? 'View File' }}
                                </a>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td>
                            @if($item->result?->is_verified)
                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>Verified</span>
                            @elseif($item->result) <span class="badge bg-warning">Unverified</span>
                            @else — @endif
                        </td>
                        <td class="text-end">
                            @if($item->result && !$item->result->is_verified)
                            @can('lab.results.create')
                            <form method="POST" action="{{ route('admin.lab.results.verify', $item->result) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-success"><i class="ti ti-check"></i> Verify</button>
                            </form>
                            @endcan
                            @endif
                            @if($request->status === 'processing' && $item->status !== 'completed')
                            @can('lab.results.create')
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#fileModal-{{ $item->id }}">
                                <i class="ti ti-upload me-1"></i>Upload
                            </button>
                            @endcan
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($request->items as $item)
@if($request->status === 'processing' && $item->status !== 'completed')
<div class="modal fade" id="fileModal-{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.lab.results.store', $item) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="result_type" value="{{ $resultType->value }}">
            <div class="modal-header">
                <h5 class="modal-title">Upload: {{ $item->name ?? $item->labTest?->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">
                        @if($resultType === \App\Enums\ResultType::IMAGE) Image File @else Document File @endif
                        <span class="text-danger">*</span>
                    </label>
                    <input type="file" name="result_file" class="form-control" required
                        accept="{{ $resultType === \App\Enums\ResultType::IMAGE ? 'image/*' : '.pdf,.doc,.docx' }}">
                    <div class="form-text">
                        @if($resultType === \App\Enums\ResultType::IMAGE) JPEG, PNG, GIF — Max 20MB
                        @else PDF, Word — Max 20MB @endif
                    </div>
                </div>
                <div class="mb-2">
                    <div class="form-check">
                        <input type="hidden" name="is_abnormal" value="0">
                        <input type="checkbox" name="is_abnormal" value="1" class="form-check-input" id="abn-f-{{ $item->id }}">
                        <label class="form-check-label text-danger" for="abn-f-{{ $item->id }}">Abnormal / Significant Finding</label>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Optional brief remark">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-upload me-1"></i>Upload</button>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach
@endif

{{-- View Result Modal (lazy-loaded via fetch) --}}
<div class="modal fade" id="viewResultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-clipboard-data me-1"></i>Investigation Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewResultBody">
                <div class="text-center py-4 text-muted"><div class="spinner-border"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.viewResultBtn');
    if (!btn) return;
    const modal = new bootstrap.Modal(document.getElementById('viewResultModal'));
    const body  = document.getElementById('viewResultBody');
    body.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border"></div></div>';
    modal.show();
    try {
        const r = await fetch(btn.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        body.innerHTML = await r.text();
    } catch (err) {
        body.innerHTML = '<div class="alert alert-danger">' + err.message + '</div>';
    }
});
</script>
@endpush
@endsection
