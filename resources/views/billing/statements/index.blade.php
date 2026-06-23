@extends('layouts.app')
@section('title', 'Patient Statements')

@section('content')
<x-page-header title="Patient Statements" icon="ti-file-description" />

<x-filter-bar :action="route('admin.billing.statements.index')" :reset-url="route('admin.billing.statements.index')">
    <div class="col-md-6"><label class="form-label small">{{ __('common.search') }}</label><input type="text" name="search" class="form-control" placeholder="Patient name or number..." value="{{ $filters['search'] ?? '' }}"></div>
</x-filter-bar>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>{{ __('common.patient') }}</th><th>Patient #</th><th class="text-end">Outstanding</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
            @forelse($patients as $patient)
                <tr><td>{{ $patient['name'] }}</td><td>{{ $patient['patient_number'] }}</td><td class="text-end">₵{{ number_format($patient['outstanding_balance'], 2) }}</td><td class="text-end"><a href="{{ $patient['url'] }}" class="btn btn-sm btn-primary">View Statement</a></td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Search for a patient to view a statement.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div></div>
@endsection
