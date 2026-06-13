@extends('layouts.app')
@section('title', 'Accounting Periods')

@section('content')
<x-page-header title="Accounting Periods" icon="ti-calendar-time" />

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

@can('accounting.periods.manage')
<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">Create Period</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.accounting.periods.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($fiscalYears as $year)
                        <option value="{{ $year->id }}" @selected(old('fiscal_year_id', request('fiscal_year_id')) == $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Start</label>
                <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">End</label>
                <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit"><i class="ti ti-plus me-1"></i>Create</button>
            </div>
        </form>
    </div>
</div>
@endcan

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    <option value="">All</option>
                    @foreach($fiscalYears as $year)
                        <option value="{{ $year->id }}" @selected(request('fiscal_year_id') == $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary" type="submit"><i class="ti ti-search"></i></button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.accounting.periods.index') }}"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Period</th>
                    <th>Fiscal Year</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th>Closed</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($periods as $period)
                <tr>
                    <td class="fw-semibold">{{ $period->name }}</td>
                    <td>{{ $period->fiscalYear?->name }}</td>
                    <td>{{ $period->start_date->format('d M Y') }}</td>
                    <td>{{ $period->end_date->format('d M Y') }}</td>
                    <td><span class="badge bg-{{ $period->status->color() }}">{{ $period->status->translatedLabel() }}</span></td>
                    <td>{{ $period->closed_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td class="text-end">
                        @if($period->status->value === 'open')
                            @can('accounting.periods.manage')
                                <x-confirm-form :action="route('admin.accounting.periods.close', $period)" method="PATCH" button-label="Close" button-class="btn btn-sm btn-outline-danger" icon="ti-lock" confirm-title="Close period?" confirm-text="Closed periods cannot receive new journal postings." confirm-button="Close" />
                            @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No accounting periods found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3 d-flex justify-content-end">{{ $periods->links() }}</div>
@endsection
