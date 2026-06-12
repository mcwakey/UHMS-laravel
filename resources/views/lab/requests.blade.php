@extends('layouts.app')
@section('title', __('lab.investigation_requests'))

@section('content')
<!-- Page Header -->
<div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-microscope me-2"></i>{{ __('lab.investigation_requests') }}</h4>
    </div>
</div>

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
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('lab.urgency') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('lab.progress') }}</th>
                        <th>{{ __('common.date') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr>
                        <td>
                            <a href="{{ $req->status === 'pending' ? route('admin.lab.requests.show', $req) : route('admin.lab.results.show', $req) }}" class="fw-medium text-primary">
                                {{ $req->request_number }}
                            </a>
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
                        <td>
                            @php $rt = $req->result_type; @endphp
                            @if($rt && $rt->value !== 'none')
                            <span class="badge bg-{{ $rt->color() }}"><i class="ti {{ $rt->icon() }} me-1"></i>{{ $rt->translatedLabel() }}</span>
                            @else <span class="text-muted">&mdash;</span> @endif
                        </td>
                        <td><x-status-badge :status="$req->urgency" domain="priority" /></td>
                        <td><span class="badge bg-{{ $req->status_color }}">{{ \Illuminate\Support\Facades\Lang::has('statuses.default.'.$req->status) ? __('statuses.default.'.$req->status) : $req->status_label }}</span></td>
                        <td>
                            <div class="progress" style="height: 6px; width: 80px;">
                                <div class="progress-bar bg-success" style="width: {{ $req->completion_percentage }}%"></div>
                            </div>
                            <small class="text-muted">{{ $req->completion_percentage }}%</small>
                        </td>
                        <td>
                            <small>{{ $req->created_at->translatedFormat('d M Y') }}</small><br>
                            <small class="text-muted">{{ $req->created_at->format('H:i') }}</small>
                        </td>
                        <td class="text-end">
                            @if($req->status === 'pending')
                            <a href="{{ route('admin.lab.requests.show', $req) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-receipt me-1"></i>{{ __('lab.bill') }}
                            </a>
                            @else
                            <a href="{{ route('admin.lab.results.show', $req) }}" class="btn btn-sm btn-outline-success">
                                <i class="ti ti-report-medical me-1"></i>{{ __('lab.results') }}
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
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
@endsection
