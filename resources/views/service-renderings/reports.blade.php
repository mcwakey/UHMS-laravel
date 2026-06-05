@extends('layouts.app')
@section('title', 'Service Rendering Reports')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1"><i class="ti ti-report-analytics me-2 text-primary"></i>Service Rendering Reports</h4>
        <p class="text-muted mb-0">Operational fulfilment counts for billed non-specialized services.</p>
    </div>
    <a href="{{ route('admin.service-renderings.index', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>Back to Worklist
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1">{{ $summary['total'] ?? 0 }}</h3><p class="text-muted mb-0">Total</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-warning">{{ $summary['pending'] ?? 0 }}</h3><p class="text-muted mb-0">Pending</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-info">{{ $summary['in_progress'] ?? 0 }}</h3><p class="text-muted mb-0">In Progress</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-success">{{ $summary['rendered'] ?? 0 }}</h3><p class="text-muted mb-0">Rendered</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-danger">{{ $summary['not_rendered'] ?? 0 }}</h3><p class="text-muted mb-0">Not Rendered</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-secondary">{{ $summary['cancelled'] ?? 0 }}</h3><p class="text-muted mb-0">Cancelled</p></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.service-renderings.reports') }}" class="row g-2 align-items-end" data-auto-filter-form="service-renderings-reports">
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm" name="status">
                    <option value="">All</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Department</label>
                <select class="form-select form-select-sm" name="department_id">
                    <option value="">All</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string)($filters['department_id'] ?? '') === (string)$department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                @include('partials.date-range-filter', [
                    'id' => 'serviceRenderingReportDateRangePicker',
                    'value' => $filters['date_range'] ?? '',
                    'labelClass' => 'small',
                    'submitOnApply' => true,
                ])
            </div>
            <div class="col-md-auto">
                <div class="d-flex gap-1">
                    <button class="btn btn-primary btn-sm" type="submit"><i class="ti ti-filter me-1"></i>Apply</button>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.service-renderings.reports') }}"><i class="ti ti-x"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">By Department</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Department</th>
                                <th>Status</th>
                                <th class="text-end">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byDepartment as $department => $rows)
                                @foreach($rows as $row)
                                    <tr>
                                        <td>{{ $department }}</td>
                                        <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $row->status) }}</span></td>
                                        <td class="text-end">{{ $row->total }}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="3"><x-empty-state message="No report data for the selected filters." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">Rendered By Staff</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Staff</th>
                                <th class="text-end">Rendered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byStaff as $row)
                                <tr>
                                    <td>{{ $row->renderedBy?->full_name ?? $row->renderedBy?->name ?? 'Unknown' }}</td>
                                    <td class="text-end">{{ $row->total }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2"><x-empty-state message="No rendered services yet." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('partials.date-range-filter-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.querySelector('[data-auto-filter-form="service-renderings-reports"]');
            if (!filterForm) {
                return;
            }

            filterForm.querySelectorAll('select').forEach(function (select) {
                select.addEventListener('change', function () {
                    filterForm.requestSubmit();
                });
            });
        });
    </script>
@endpush
