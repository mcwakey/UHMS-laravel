@extends('layouts.app')
@section('title', __('accounting.close_readiness'))

@section('content')
<x-page-header :title="__('accounting.close_readiness')" :description="__('accounting.close_readiness_description')" icon="ti-checkup-list">
    <x-slot:actions><div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ route('admin.accounting.subledger-reconciliation.index') }}">{{ __('accounting.subledger_reconciliation') }}</a><a class="btn btn-outline-danger" href="{{ route('admin.accounting.failed-postings.index') }}">{{ __('accounting.failed_postings') }}</a></div></x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">{{ __('accounting.from') }}</label><input class="form-control" type="date" name="from" value="{{ $summary['from'] }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('accounting.to') }}</label><input class="form-control" type="date" name="to" value="{{ $summary['to'] }}"></div>
            <div class="col-md-2"><button class="btn btn-primary" type="submit">{{ __('accounting.check_readiness') }}</button></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.attempt_status_resolved') }}</div><div class="fs-4 fw-bold text-info">{{ $summary['resolved_postings'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.unsupported') }}</div><div class="fs-4 fw-bold text-danger">{{ $summary['unsupported_failed_postings'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.posted_after_retry') }}</div><div class="fs-4 fw-bold text-success">{{ $summary['posted_after_retry'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.oldest_unresolved_failure') }}</div><div class="fw-semibold">{{ $summary['oldest_unresolved_failure'] ?? '-' }}</div></div></div></div>
</div>

<div class="alert alert-{{ $summary['ready'] ? 'success' : 'warning' }}">
    {{ $summary['ready'] ? __('accounting.close_ready') : __('accounting.close_not_ready') }}
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.unresolved_failed_postings') }}</div><div class="fs-3 fw-bold text-danger">{{ $summary['unresolved_failed_postings'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.waived_postings') }}</div><div class="fs-3 fw-bold text-warning">{{ $summary['waived_postings'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.posted_attempts') }}</div><div class="fs-3 fw-bold text-success">{{ $summary['posted_attempts'] }}</div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.unapproved_reconciliation_runs') }}</div><div class="fs-4 fw-bold text-warning">{{ $summary['unapproved_reconciliation_runs'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.domains_with_unresolved_differences') }}</div><div class="fs-4 fw-bold text-danger">{{ count($summary['domains_with_unresolved_differences']) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.domains_not_run_for_period') }}</div><div class="fs-4 fw-bold text-secondary">{{ count($summary['domains_not_run_for_period']) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.manual_control_account_journals') }}</div><div class="fs-4 fw-bold text-warning">{{ $summary['manual_control_account_journals'] }}</div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('accounting.latest_reconciliation_by_domain') }}</h5></div>
    <div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>{{ __('accounting.reconciliation_type') }}</th><th>{{ __('common.status') }}</th><th>{{ __('accounting.availability') }}</th><th>{{ __('accounting.difference_amount') }}</th><th>{{ __('accounting.last_reconciliation_date') }}</th></tr></thead><tbody>
    @foreach($summary['latest_reconciliation_by_domain'] as $type => $run)
        <tr><td>{{ __('accounting.reconciliation_type_'.$type) }}</td><td>{{ $run ? __('accounting.reconciliation_status_'.$run['status']) : __('accounting.not_run') }}</td><td>{{ $run ? __('accounting.availability_'.$run['availability']) : '-' }}</td><td>{{ $run ? number_format($run['difference_amount'], 2) : '-' }}</td><td>{{ $run['completed_at'] ?? '-' }}</td></tr>
    @endforeach
    </tbody></table></div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('accounting.failed_by_source_module') }}</h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light"><tr><th>{{ __('accounting.source_module') }}</th><th>{{ __('accounting.unresolved_failed_postings') }}</th><th>{{ __('accounting.waived_postings') }}</th><th>{{ __('accounting.attempt_status_resolved') }}</th></tr></thead>
            <tbody>
                @forelse(collect(array_keys($summary['failed_by_source_module'] + $summary['waived_by_source_module'] + $summary['resolved_by_source_module']))->unique()->sort() as $module)
                    <tr><td>{{ $module }}</td><td>{{ $summary['failed_by_source_module'][$module] ?? 0 }}</td><td>{{ $summary['waived_by_source_module'][$module] ?? 0 }}</td><td>{{ $summary['resolved_by_source_module'][$module] ?? 0 }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('accounting.no_close_readiness_exceptions') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
