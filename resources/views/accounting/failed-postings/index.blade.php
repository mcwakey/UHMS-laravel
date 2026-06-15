@extends('layouts.app')
@section('title', __('accounting.failed_posting_workbench'))

@section('content')
<x-page-header :title="__('accounting.failed_posting_workbench')" :description="__('accounting.failed_postings_description')" icon="ti-alert-triangle" />

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-3 mb-3">
    @foreach([
        ['failed_unresolved', 'unresolved_failed_postings', 'danger'],
        ['failed_over_7_days', 'failed_over_7_days', 'warning'],
        ['failed_over_30_days', 'failed_over_30_days', 'danger'],
        ['waived', 'waived_postings', 'secondary'],
        ['resolved', 'attempt_status_resolved', 'info'],
        ['posted_after_retry', 'posted_after_retry', 'success'],
    ] as [$key, $label, $colour])
    <div class="col-md-2"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small">{{ __('accounting.'.$label) }}</div><div class="fs-3 fw-bold text-{{ $colour }}">{{ $dashboard[$key] }}</div></div></div></div>
    @endforeach
</div>

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-2"><label class="form-label small">{{ __('common.status') }}</label><select class="form-select" name="status"><option value="">{{ __('accounting.attempt_status_failed') }}</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ __('accounting.attempt_status_'.$status) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small">{{ __('accounting.source_module') }}</label><select class="form-select" name="source_module"><option value="">{{ __('common.all') }}</option>@foreach($sourceModules as $module)<option value="{{ $module }}" @selected(request('source_module') === $module)>{{ $module }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small">{{ __('accounting.source_type') }}</label><select class="form-select" name="source_type"><option value="">{{ __('common.all') }}</option>@foreach($sourceTypes as $type)<option value="{{ $type }}" @selected(request('source_type') === $type)>{{ $type }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small">{{ __('accounting.posting_type') }}</label><select class="form-select" name="posting_type"><option value="">{{ __('common.all') }}</option>@foreach($postingTypes as $type)<option value="{{ $type }}" @selected(request('posting_type') === $type)>{{ $type }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small">{{ __('accounting.error_code') }}</label><input class="form-control" name="error_code" value="{{ request('error_code') }}"></div>
        <div class="col-md-1"><label class="form-label small">{{ __('accounting.attempt_count') }}</label><input type="number" min="1" class="form-control" name="attempt_count" value="{{ request('attempt_count') }}"></div>
        <div class="col-md-1"><button class="btn btn-primary w-100" title="{{ __('common.search') }}"><i class="ti ti-search"></i></button></div>
        <div class="col-md-2"><label class="form-label small">{{ __('accounting.from') }}</label><input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="col-md-2"><label class="form-label small">{{ __('accounting.to') }}</label><input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}"></div>
        <div class="col-md-2"><label class="form-label small">{{ __('accounting.has_journal') }}</label><select class="form-select" name="has_journal"><option value="">{{ __('common.all') }}</option><option value="1" @selected(request('has_journal') === '1')>{{ __('common.yes') }}</option><option value="0" @selected(request('has_journal') === '0')>{{ __('common.no') }}</option></select></div>
        <div class="col-md-2"><label class="form-label small">{{ __('accounting.has_reversal') }}</label><select class="form-select" name="has_reversal"><option value="">{{ __('common.all') }}</option><option value="1" @selected(request('has_reversal') === '1')>{{ __('common.yes') }}</option><option value="0" @selected(request('has_reversal') === '0')>{{ __('common.no') }}</option></select></div>
        <div class="col-md-2"><label class="form-label small">{{ __('accounting.resolved_by') }}</label><select class="form-select" name="resolved_by"><option value="">{{ __('common.all') }}</option>@foreach($resolvers as $resolver)<option value="{{ $resolver->id }}" @selected(request('resolved_by') == $resolver->id)>{{ $resolver->name }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex gap-2"><a class="btn btn-outline-secondary w-100" href="{{ route('admin.accounting.failed-postings.index') }}">{{ __('accounting.clear') }}</a></div>
    </form>
</div></div>

<form method="POST" action="{{ route('admin.accounting.failed-postings.retry-selected') }}">
@csrf
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th></th><th>{{ __('common.status') }}</th><th>{{ __('accounting.source_module') }}</th><th>{{ __('accounting.source_record') }}</th><th>{{ __('accounting.posting_type') }}</th><th>{{ __('accounting.attempt_count') }}</th><th>{{ __('accounting.error_code') }}</th><th>{{ __('accounting.error_message') }}</th><th>{{ __('accounting.last_attempted_at') }}</th><th>{{ __('accounting.journal') }}</th><th>{{ __('accounting.created_by') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($attempts as $attempt)
                <tr>
                    <td>@if($attempt->status === 'failed')<input class="form-check-input" type="checkbox" name="attempt_ids[]" value="{{ $attempt->id }}">@endif</td>
                    <td><span class="badge bg-{{ ['failed'=>'danger','posted'=>'success','waived'=>'secondary','resolved'=>'info','reversed'=>'dark'][$attempt->status] ?? 'warning' }}">{{ __('accounting.attempt_status_'.$attempt->status) }}</span></td>
                    <td>{{ $attempt->source_module }}</td>
                    <td>{{ $attempt->source_type }} #{{ $attempt->source_id }}</td>
                    <td>{{ $attempt->posting_type }} v{{ $attempt->posting_version }}</td>
                    <td>{{ $attempt->attempt_count }}</td>
                    <td>{{ $attempt->error_code ?? '-' }}</td>
                    <td title="{{ $attempt->error_message }}">{{ Str::limit($attempt->error_message, 65) ?: '-' }}</td>
                    <td>{{ $attempt->last_attempted_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td>@if($attempt->journalEntry)<a href="{{ route('admin.accounting.journals.show', $attempt->journalEntry) }}">{{ $attempt->journalEntry->journal_number }}</a>@else - @endif</td>
                    <td>{{ $attempt->createdBy?->name ?? '-' }}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.accounting.failed-postings.show', $attempt) }}" title="{{ __('common.view') }}"><i class="ti ti-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="12" class="text-center text-muted py-4">{{ __('accounting.no_posting_attempts') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@can('accounting.failed_postings.retry')
<button class="btn btn-warning mt-3" type="submit"><i class="ti ti-refresh me-1"></i>{{ __('accounting.retry_selected') }}</button>
@endcan
</form>
<div class="mt-3 d-flex justify-content-end">{{ $attempts->links() }}</div>

@if($dashboard['by_source_module']->isNotEmpty())
<div class="card mt-3"><div class="card-header"><h5 class="mb-0">{{ __('accounting.failed_by_source_module') }}</h5></div><div class="card-body d-flex flex-wrap gap-2">@foreach($dashboard['by_source_module'] as $module => $total)<span class="badge bg-light text-dark border">{{ $module }}: {{ $total }}</span>@endforeach</div></div>
@endif
@endsection
