@extends('layouts.app')
@section('title', __('accounting.close_readiness'))

@section('content')
<x-page-header :title="__('accounting.close_readiness')" :description="__('accounting.close_readiness_description')" icon="ti-checkup-list" />

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">{{ __('accounting.from') }}</label><input class="form-control" type="date" name="from" value="{{ $summary['from'] }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('accounting.to') }}</label><input class="form-control" type="date" name="to" value="{{ $summary['to'] }}"></div>
            <div class="col-md-2"><button class="btn btn-primary" type="submit">{{ __('accounting.check_readiness') }}</button></div>
        </form>
    </div>
</div>

<div class="alert alert-{{ $summary['ready'] ? 'success' : 'warning' }}">
    {{ $summary['ready'] ? __('accounting.close_ready') : __('accounting.close_not_ready') }}
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.unresolved_failed_postings') }}</div><div class="fs-3 fw-bold text-danger">{{ $summary['unresolved_failed_postings'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.waived_postings') }}</div><div class="fs-3 fw-bold text-warning">{{ $summary['waived_postings'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">{{ __('accounting.posted_attempts') }}</div><div class="fs-3 fw-bold text-success">{{ $summary['posted_attempts'] }}</div></div></div></div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('accounting.failed_by_source_module') }}</h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light"><tr><th>{{ __('accounting.source_module') }}</th><th>{{ __('accounting.unresolved_failed_postings') }}</th><th>{{ __('accounting.waived_postings') }}</th></tr></thead>
            <tbody>
                @forelse(collect(array_keys($summary['failed_by_source_module'] + $summary['waived_by_source_module']))->unique()->sort() as $module)
                    <tr><td>{{ $module }}</td><td>{{ $summary['failed_by_source_module'][$module] ?? 0 }}</td><td>{{ $summary['waived_by_source_module'][$module] ?? 0 }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">{{ __('accounting.no_close_readiness_exceptions') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
