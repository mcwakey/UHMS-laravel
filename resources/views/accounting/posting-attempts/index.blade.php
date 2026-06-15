@extends('layouts.app')
@section('title', __('accounting.posting_attempts'))

@section('content')
<x-page-header :title="__('accounting.posting_attempts')" :description="__('accounting.posting_attempts_description')" icon="ti-history-toggle" />

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">{{ __('common.search') }}</label>
                <input class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('accounting.search_posting_attempts') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('common.status') }}</label>
                <select class="form-select" name="status">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('accounting.attempt_status_'.$status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('accounting.source_module') }}</label>
                <select class="form-select" name="source_module">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($sourceModules as $module)
                        <option value="{{ $module }}" @selected(request('source_module') === $module)>{{ $module }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit"><i class="ti ti-search"></i></button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.accounting.posting-attempts.index') }}"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('accounting.source_module') }}</th>
                    <th>{{ __('accounting.source_record') }}</th>
                    <th>{{ __('accounting.posting_type') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th>{{ __('accounting.attempt_count') }}</th>
                    <th>{{ __('accounting.last_attempted_at') }}</th>
                    <th>{{ __('accounting.journal') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attempts as $attempt)
                    <tr>
                        <td>{{ $attempt->source_module }}</td>
                        <td>
                            <a href="{{ route('admin.accounting.posting-attempts.show', $attempt) }}" class="fw-semibold">
                                {{ $attempt->source_type }} #{{ $attempt->source_id }}
                            </a>
                        </td>
                        <td>{{ $attempt->posting_type }} v{{ $attempt->posting_version }}</td>
                        <td><span class="badge bg-{{ $attempt->status === 'posted' ? 'success' : ($attempt->status === 'failed' ? 'danger' : 'secondary') }}">{{ __('accounting.attempt_status_'.$attempt->status) }}</span></td>
                        <td>{{ $attempt->attempt_count }}</td>
                        <td>{{ $attempt->last_attempted_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>
                            @if($attempt->journalEntry)
                                <a href="{{ route('admin.accounting.journals.show', $attempt->journalEntry) }}">{{ $attempt->journalEntry->journal_number }}</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('accounting.no_posting_attempts') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3 d-flex justify-content-end">{{ $attempts->links() }}</div>
@endsection
