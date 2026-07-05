@extends('layouts.app')
@section('title', $report['title'])
@section('content')
<x-page-header :title="$report['title']" icon="ti-report-analytics">
    <x-slot:actions>
        @can('maternity.reports.export')
        <a href="{{ route('admin.maternity.reports.export', array_merge(request()->query(), ['report' => $reportKey])) }}" class="btn btn-outline-success btn-md fs-13"><i class="ti ti-download me-1"></i>{{ __('maternity.export_csv') }}</a>
        @endcan
        <a href="{{ route('admin.maternity.reports.index') }}" class="btn btn-outline-secondary btn-md fs-13">{{ __('common.back') }}</a>
    </x-slot:actions>
</x-page-header>

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label class="form-label">{{ __('maternity.date_from') }}</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.date_to') }}</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.department') }}</label><select name="department_id" class="form-select"><option value="">{{ __('maternity.all') }}</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) $filters['department_id'] === (string) $department->id)>{{ $department->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.recorded_by') }}</label><select name="staff_id" class="form-select"><option value="">{{ __('maternity.all') }}</option>@foreach($staff as $user)<option value="{{ $user->id }}" @selected((string) $filters['staff_id'] === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('maternity.filter') }}</button></div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    @foreach($report['metrics'] ?? [] as $key => $value)
    <div class="col-6 col-md-4 col-xl-3">
        <div class="card h-100"><div class="card-body py-3"><div class="fs-4 fw-bold">{{ $value }}</div><small class="text-muted">{{ __('maternity.report_metrics.'.$key) }}</small></div></div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr>@foreach($report['columns'] as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                    <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                    @empty
                    <tr><td colspan="{{ count($report['columns']) }}"><x-empty-state icon="ti-report" :title="__('maternity.no_records_found')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(is_object($report['rows']) && method_exists($report['rows'], 'links'))<div class="card-footer">{{ $report['rows']->links() }}</div>@endif
</div>
@endsection
