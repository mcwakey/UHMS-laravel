@extends('layouts.app')
@section('title', __('lab.investigation_requests'))

@section('content')
<!-- Page Header -->
<div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-microscope me-2"></i>{{ __('lab.investigation_requests') }}</h4>
    </div>
    @can('lab.requests.create')
    <button type="button" class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#newInvestigationRequestModal">
        <i class="ti ti-plus me-1"></i>{{ __('lab.new_request') }}
    </button>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Stats Cards -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-warning">{{ $stats['pending'] }}</h3>
                <small class="text-muted">{{ __('lab.pending') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-info">{{ $stats['processing'] }}</h3>
                <small class="text-muted">{{ __('lab.processing') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-success">{{ $stats['completed_today'] }}</h3>
                <small class="text-muted">{{ __('lab.completed_today') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-primary">{{ $stats['total_tests'] }}</h3>
                <small class="text-muted">{{ __('lab.active_tests') }}</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">{{ __('common.search') }}</label>
                <input type="text" name="search" class="form-control" placeholder="{{ __('lab.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('common.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('lab.all_status') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('lab.pending') }}</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>{{ __('lab.processing') }}</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('lab.completed') }}</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('lab.cancelled') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('common.department') }}</label>
                <select name="department_id" class="form-select">
                    <option value="">{{ __('lab.all_departments') }}</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('lab.urgency') }}</label>
                <select name="urgency" class="form-select">
                    <option value="">{{ __('lab.all_urgency') }}</option>
                    <option value="routine" {{ request('urgency') === 'routine' ? 'selected' : '' }}>{{ __('lab.routine') }}</option>
                    <option value="urgent" {{ request('urgency') === 'urgent' ? 'selected' : '' }}>{{ __('lab.urgent') }}</option>
                    <option value="emergency" {{ request('urgency') === 'emergency' ? 'selected' : '' }}>{{ __('lab.emergency') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('common.from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="{{ __('common.from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('common.to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="{{ __('common.to') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>{{ __('common.filter') }}</button>
                <a href="{{ route('admin.lab.requests.index') }}" class="btn btn-outline-secondary btn-md"><i class="ti ti-x me-1"></i>{{ __('common.clear') }}</a>
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
                        <th>{{ __('lab.request_number_short') }}</th>
                        <th>{{ __('visits.queue_number') }}</th>
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <!-- <th>{{ __('common.type') }}</th> -->
                        <th>{{ __('lab.urgency') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <!-- <th>{{ __('lab.progress') }}</th> -->
                        <th>{{ __('common.date') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    @php
                        $queueEntries = $req->visit?->queueEntries ?? collect();
                        $queueEntry = $queueEntries
                            ->first(fn ($entry) => (int) $entry->department_id === (int) $req->target_department_id && in_array($entry->status, ['waiting', 'serving'], true))
                            ?? $queueEntries->first(fn ($entry) => (int) $entry->department_id === (int) $req->target_department_id)
                            ?? $queueEntries->first(fn ($entry) => in_array($entry->status, ['waiting', 'serving'], true))
                            ?? $queueEntries->first();
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.lab.requests.show', $req) }}" class="fw-medium text-primary">
                                {{ $req->request_number }}
                            </a>
                        </td>
                        <td>
                            @if($queueEntry)
                                <span class="badge bg-soft-primary text-primary">#{{ $queueEntry->queue_number }}</span>
                                <div class="small text-muted">{{ $queueEntry->status_label }}</div>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-medium">{{ $req->patient?->full_name ?? $req->external_party_name ?? '—' }}</div>
                            <small class="text-muted">{{ $req->patient?->patient_number ?? ($req->external_party_name ? __('lab.walk_in') : '') }}</small>
                        </td>
                        <td>
                            @if($req->targetDepartment)
                            <span class="fw-medium">{{ $req->targetDepartment->name }}</span>
                            @else <span class="text-muted">&mdash;</span> @endif
                        </td>
                        <!-- <td>
                            @php $rt = $req->result_type; @endphp
                            @if($rt && $rt->value !== 'none')
                            <span class="badge bg-{{ $rt->color() }}"><i class="ti {{ $rt->icon() }} me-1"></i>{{ $rt->translatedLabel() }}</span>
                            @else <span class="text-muted">&mdash;</span> @endif
                        </td> -->
                        <td><x-status-badge :status="$req->urgency" domain="priority" /></td>
                        <td><span class="badge bg-{{ $req->status_color }}">{{ \Illuminate\Support\Facades\Lang::has('statuses.default.'.$req->status) ? __('statuses.default.'.$req->status) : $req->status_label }}</span></td>
                        <!-- <td>
                            <div class="progress" style="height: 6px; width: 80px;">
                                <div class="progress-bar bg-success" style="width: {{ $req->completion_percentage }}%"></div>
                            </div>
                            <small class="text-muted">{{ $req->completion_percentage }}%</small>
                        </td> -->
                        <td>
                            <small>{{ $req->created_at->translatedFormat('d M Y') }}</small><br>
                            <small class="text-muted">{{ $req->created_at->format('H:i') }}</small>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.lab.requests.show', $req) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-receipt me-1"></i>{{ __('lab.bill') }}
                            </a>
                            <!-- @if($req->status === 'pending') -->
                            <!-- @else
                            <a href="{{ route('admin.lab.results.show', $req) }}" class="btn btn-sm btn-outline-success">
                                <i class="ti ti-report-medical me-1"></i>{{ __('lab.results') }}
                            </a>
                            @endif -->
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="ti ti-microscope fs-1 d-block mb-2"></i>
                            {{ __('lab.no_requests_found') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($requests->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $requests->links() }}
</div>
@endif

@can('lab.requests.create')
<div class="modal fade" id="newInvestigationRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.lab.requests.store') }}" id="newInvestigationRequestForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold mb-0"><i class="ti ti-microscope me-1"></i>{{ __('lab.new_request_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">{{ __('lab.visit_label') }} <span class="text-danger">*</span></label>
                        <select name="visit_id" id="irVisit" class="form-select" required style="width:100%">
                            <option value="">-- Search visit number or patient name/number --</option>
                        </select>
                        <div class="form-text">{{ __('lab.select_visit_help') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">{{ __('lab.department_label') }} <span class="text-danger">*</span></label>
                        <select name="target_department_id" id="irDepartment" class="form-select" required>
                            <option value="">-- {{ __('common.select') }} --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">{{ __('lab.select_tests') }} <span class="text-danger">*</span></label>
                        <div id="irTestsHelp" class="text-muted small mb-2">{{ __('lab.select_department_first') }}</div>
                        <div id="irCatalogTests" class="row g-1 d-none" style="max-height: 220px; overflow-y: auto;"></div>
                        <textarea id="irFreeTextTests" class="form-control d-none" rows="3" placeholder="{{ __('lab.other_tests_placeholder') }}"></textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">{{ __('lab.urgency') }}</label>
                            <select name="urgency" class="form-select">
                                <option value="routine">{{ __('lab.routine') }}</option>
                                <option value="urgent">{{ __('lab.urgent') }}</option>
                                <option value="emergency">{{ __('lab.emergency') }}</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small">{{ __('lab.clinical_information') }}</label>
                            <input type="text" name="clinical_info" class="form-control" placeholder="{{ __('lab.clinical_information') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('lab.create_request_btn') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('newInvestigationRequestModal');
    const form = document.getElementById('newInvestigationRequestForm');
    const departmentSelect = document.getElementById('irDepartment');
    const catalogWrap = document.getElementById('irCatalogTests');
    const freeText = document.getElementById('irFreeTextTests');
    const help = document.getElementById('irTestsHelp');
    if (!modalEl || !form || !departmentSelect || !catalogWrap || !freeText) return;

    if (window.jQuery && jQuery.fn.select2) {
        jQuery('#irVisit').select2({
            dropdownParent: jQuery(modalEl),
            width: '100%',
            placeholder: '-- Search visit number or patient name/number --',
            minimumInputLength: 2,
            ajax: {
                url: '{{ route('admin.lab.requests.visit-search') }}',
                dataType: 'json',
                delay: 300,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) {
                    return { results: (data || []).map(function (v) {
                        v.text = v.visit_number + ' — ' + (v.patient_name || 'Patient') + ' (' + (v.patient_number || '') + ')';
                        v.id = String(v.id);
                        return v;
                    }) };
                },
                cache: true
            }
        });
    }

    departmentSelect.addEventListener('change', function () {
        const deptId = departmentSelect.value;
        catalogWrap.innerHTML = '';
        catalogWrap.classList.add('d-none');
        freeText.classList.add('d-none');
        freeText.value = '';

        if (!deptId) {
            help.textContent = @json(__('lab.select_department_first'));
            help.classList.remove('d-none');
            return;
        }

        help.textContent = 'Loading…';
        help.classList.remove('d-none');

        fetch('{{ route('admin.lab.requests.department-tests', ['department' => '__DEPT__']) }}'.replace('__DEPT__', deptId))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.uses_catalog && (data.lab_tests || []).length) {
                    help.classList.add('d-none');
                    catalogWrap.classList.remove('d-none');
                    data.lab_tests.forEach(function (test) {
                        const col = document.createElement('div');
                        col.className = 'col-md-6';
                        col.innerHTML = '<div class="form-check">' +
                            '<input class="form-check-input" type="checkbox" name="items[]" value="' + test.id + '" id="irTest' + test.id + '">' +
                            '<label class="form-check-label small" for="irTest' + test.id + '">' + test.name + '</label>' +
                            '</div>';
                        catalogWrap.appendChild(col);
                    });
                } else {
                    help.classList.add('d-none');
                    freeText.classList.remove('d-none');
                }
            })
            .catch(function () {
                help.textContent = 'Unable to load tests for this department.';
            });
    });

    form.addEventListener('submit', function () {
        form.querySelectorAll('.dynamic-free-text-item').forEach(function (el) { el.remove(); });

        if (!freeText.classList.contains('d-none') && freeText.value.trim() !== '') {
            freeText.value.split('\n').map(function (line) { return line.trim(); }).filter(Boolean).forEach(function (line) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[]';
                input.value = line;
                input.className = 'dynamic-free-text-item';
                form.appendChild(input);
            });
        }
    });
}());
</script>
@endpush
@endcan
@endsection
