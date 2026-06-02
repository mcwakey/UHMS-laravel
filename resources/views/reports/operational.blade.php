@extends('layouts.app')
@section('title', $meta['title'] ?? 'Operational Report')

@section('content')
<x-page-header :title="$meta['title']" :description="$meta['description']" icon="ti-report-analytics">
    <x-slot:actions>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.reports.dashboard') }}">Reports Dashboard</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.reports.'.$key, array_merge($filters, ['export' => 'csv'])) }}"><i class="ti ti-file-type-csv me-1"></i>Export CSV</a>
    </x-slot:actions>
</x-page-header>

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Status / Type</label><input name="status" value="{{ $filters['status'] ?? '' }}" class="form-control" placeholder="Optional"></div>
            <div class="col-md-2">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All</option>
                    @foreach($departments as $department)<option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>{{ $department->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">User / Staff</label>
                <select name="user_id" class="form-select">
                    <option value="">All</option>
                    @foreach($users as $user)<option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary w-100">Run</button><a class="btn btn-outline-secondary" href="{{ route('admin.reports.'.$key) }}">Clear</a></div>
        </div>
        @if($key === 'blood-bank')
            <div class="row g-2 mt-1">
                <div class="col-md-2">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">All</option>
                        @foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $group)
                            <option value="{{ $group }}" @selected(($filters['blood_group'] ?? '') === $group)>{{ $group }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-2"><div class="card border-0 bg-light"><div class="card-body py-3"><div class="small text-muted">Total Rows</div><div class="h4 mb-0">{{ $summary['total'] ?? 0 }}</div></div></div></div>
    @foreach(($summary['status_counts'] ?? []) as $status => $total)
        <div class="col-6 col-xl-2"><div class="card border-0 bg-light"><div class="card-body py-3"><div class="small text-muted">{{ str_replace('_', ' ', $status ?: 'Unknown') }}</div><div class="h4 mb-0">{{ $total }}</div></div></div></div>
    @endforeach
    @foreach($summary as $label => $value)
        @continue(in_array($label, ['total', 'status_counts'], true))
        <div class="col-6 col-xl-2"><div class="card border-0 bg-light"><div class="card-body py-3"><div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div><div class="h4 mb-0">{{ is_numeric($value) ? number_format($value) : $value }}</div></div></div></div>
    @endforeach
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>@foreach($columns as $column)<th>{{ $column }}</th>@endforeach</tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>@foreach($row as $cell)<td>{{ $cell ?: '—' }}</td>@endforeach</tr>
                    @empty
                        <tr><td colspan="{{ count($columns) }}" class="text-center text-muted py-4">No data matches this report.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($rows instanceof \Illuminate\Contracts\Pagination\Paginator && $rows->hasPages())
        <div class="card-footer">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
