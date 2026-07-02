@extends('layouts.app')
@section('title', __('reports.statement.search_title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('reports.statement.search_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('reports.statement.search_title') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.statement-search') }}" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">{{ __('reports.statement.search_title') }}</label>
                <input type="text" name="q" class="form-control" placeholder="{{ __('reports.statement.search_placeholder') }}" value="{{ request('q') }}">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary"><i class="ti ti-search me-1"></i>{{ __('reports.search') }}</button>
            </div>
        </form>
    </div>
</div>

@if(isset($patients))
<div class="card">
    <div class="card-header"><h6 class="mb-0">{{ __('reports.search') }} ({{ $patients->total() }})</h6></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('reports.statement.patient_id') }}</th>
                    <th>{{ __('reports.statement.patient_name') }}</th>
                    <th>{{ __('reports.patients.phone') }}</th>
                    <th>{{ __('reports.patients.gender') }}</th>
                    <th>{{ __('common.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patients as $patient)
                <tr>
                    <td><code>{{ $patient->patient_number }}</code></td>
                    <td>{{ $patient->full_name }}</td>
                    <td><x-patient-protected-field field="phone" :value="$patient->phone" /></td>
                    <td>{{ $patient->gender?->label() ?? '—' }}</td>
                    <td>
                        <a href="{{ route('admin.reports.patient-statement', $patient) }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-file-invoice me-1"></i>{{ __('reports.statement.title') }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('reports.empty.no_patients') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($patients->hasPages())
    <div class="card-footer">{{ $patients->links() }}</div>
    @endif
</div>
@else
<div class="text-center text-muted py-5">
    <i class="ti ti-search fs-1 d-block mb-2"></i>
    <p>{{ __('reports.statement.search_prompt') }}</p>
</div>
@endif
@endsection
