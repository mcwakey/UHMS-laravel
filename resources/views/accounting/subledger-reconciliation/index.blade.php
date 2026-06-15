@extends('layouts.app')
@section('title', __('accounting.subledger_reconciliation'))

@section('content')
<x-page-header :title="__('accounting.subledger_reconciliation')" :description="__('accounting.subledger_reconciliation_description')" icon="ti-scale">
    <x-slot:actions>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounting.subledger-reconciliation.history') }}"><i class="ti ti-history me-1"></i>{{ __('accounting.reconciliation_history') }}</a>
        @can('accounting.subledger_reconciliation.run')
        <a class="btn btn-primary" href="{{ route('admin.accounting.subledger-reconciliation.create') }}"><i class="ti ti-player-play me-1"></i>{{ __('accounting.run_reconciliation') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="row g-3 mb-4">
@foreach([
    ['balanced_domains', 'balanced_domains', 'success', 'ti-circle-check'],
    ['difference_detected', 'difference_detected', 'danger', 'ti-arrows-difference'],
    ['failed_posting_linked', 'failed_posting_linked', 'warning', 'ti-alert-triangle'],
    ['manual_journals_detected', 'manual_journals_detected', 'orange', 'ti-pencil'],
    ['unposted_source_records', 'unposted_source_records', 'info', 'ti-clock'],
] as [$key, $label, $colour, $icon])
<div class="col-md"><div class="card h-100"><div class="card-body py-3"><div class="d-flex justify-content-between"><div><div class="text-muted small">{{ __('accounting.'.$label) }}</div><div class="fs-3 fw-bold text-{{ $colour }}">{{ $dashboard[$key] }}</div></div><i class="ti {{ $icon }} fs-2 text-{{ $colour }}"></i></div></div></div></div>
@endforeach
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">{{ __('accounting.reconciliation_domains') }}</h5></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>{{ __('accounting.reconciliation_type') }}</th><th>{{ __('accounting.availability') }}</th><th>{{ __('accounting.subledger_total') }}</th><th>{{ __('accounting.gl_total') }}</th><th>{{ __('accounting.difference_amount') }}</th><th>{{ __('common.status') }}</th><th>{{ __('accounting.last_reconciliation_date') }}</th><th></th></tr></thead>
            <tbody>
            @foreach($types as $type)
                @php($run = $dashboard['latest']->get($type))
                <tr>
                    <td class="fw-semibold">{{ __('accounting.reconciliation_type_'.$type) }}</td>
                    <td><span class="badge bg-{{ !$run ? 'secondary' : ($run->availability() === 'available' ? 'success' : ($run->availability() === 'partially_available' ? 'warning' : 'secondary')) }}">{{ __('accounting.availability_'.($run?->availability() ?? 'not_run')) }}</span></td>
                    <td>{{ $run ? number_format((float)$run->subledger_total, 2) : '-' }}</td>
                    <td>{{ $run ? number_format((float)$run->gl_total, 2) : '-' }}</td>
                    <td class="{{ $run && abs((float)$run->difference_amount) > (float)$run->tolerance_amount ? 'text-danger fw-bold' : 'text-success' }}">{{ $run ? number_format((float)$run->difference_amount, 2) : '-' }}</td>
                    <td>{{ $run ? __('accounting.reconciliation_status_'.$run->status) : __('accounting.not_run') }}</td>
                    <td>{{ $run?->completed_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td>@if($run)<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.accounting.subledger-reconciliation.show', $run) }}"><i class="ti ti-eye"></i></a>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">{{ __('accounting.recent_reconciliation_runs') }}</h5></div>
    <div class="table-responsive"><table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>#</th><th>{{ __('accounting.reconciliation_type') }}</th><th>{{ __('accounting.period') }}</th><th>{{ __('accounting.difference_amount') }}</th><th>{{ __('common.status') }}</th><th>{{ __('accounting.started_by') }}</th><th></th></tr></thead>
        <tbody>@forelse($recentRuns as $run)<tr><td>{{ $run->id }}</td><td>{{ __('accounting.reconciliation_type_'.$run->reconciliation_type) }}</td><td>{{ $run->period_start->format('d M Y') }} - {{ $run->period_end->format('d M Y') }}</td><td>{{ number_format((float)$run->difference_amount, 2) }}</td><td>{{ __('accounting.reconciliation_status_'.$run->status) }}</td><td>{{ $run->startedBy?->name ?? '-' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.accounting.subledger-reconciliation.show', $run) }}"><i class="ti ti-eye"></i></a></td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">{{ __('accounting.no_reconciliation_runs') }}</td></tr>@endforelse</tbody>
    </table></div>
</div>
@endsection
