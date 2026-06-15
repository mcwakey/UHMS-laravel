@extends('layouts.app')
@section('title', __('accounting.posting_attempt'))

@section('content')
<x-page-header :title="__('accounting.posting_attempt')" :description="$postingAttempt->idempotency_key" icon="ti-history-toggle">
    <x-slot:actions>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounting.posting-attempts.index') }}">{{ __('common.back') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('accounting.posting_attempt') }}</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('accounting.source_module') }}</dt><dd class="col-sm-8">{{ $postingAttempt->source_module }}</dd>
                    <dt class="col-sm-4">{{ __('accounting.source_type') }}</dt><dd class="col-sm-8">{{ $postingAttempt->source_type }}</dd>
                    <dt class="col-sm-4">{{ __('accounting.source_id') }}</dt><dd class="col-sm-8">{{ $postingAttempt->source_id }}</dd>
                    <dt class="col-sm-4">{{ __('accounting.posting_type') }}</dt><dd class="col-sm-8">{{ $postingAttempt->posting_type }}</dd>
                    <dt class="col-sm-4">{{ __('accounting.posting_version') }}</dt><dd class="col-sm-8">{{ $postingAttempt->posting_version }}</dd>
                    <dt class="col-sm-4">{{ __('common.status') }}</dt><dd class="col-sm-8">{{ __('accounting.attempt_status_'.$postingAttempt->status) }}</dd>
                    <dt class="col-sm-4">{{ __('accounting.attempt_count') }}</dt><dd class="col-sm-8">{{ $postingAttempt->attempt_count }}</dd>
                    <dt class="col-sm-4">{{ __('accounting.last_attempted_at') }}</dt><dd class="col-sm-8">{{ $postingAttempt->last_attempted_at?->format('d M Y H:i:s') ?? '-' }}</dd>
                    <dt class="col-sm-4">{{ __('accounting.next_retry_at') }}</dt><dd class="col-sm-8">{{ $postingAttempt->next_retry_at?->format('d M Y H:i:s') ?? '-' }}</dd>
                    <dt class="col-sm-4">{{ __('accounting.journal') }}</dt>
                    <dd class="col-sm-8">
                        @if($postingAttempt->journalEntry)
                            <a href="{{ route('admin.accounting.journals.show', $postingAttempt->journalEntry) }}">{{ $postingAttempt->journalEntry->journal_number }}</a>
                        @else
                            -
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('accounting.attempt_history') }}</h5></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>{{ __('common.date') }}</th><th>{{ __('accounting.event') }}</th><th>{{ __('common.status') }}</th><th>{{ __('accounting.user') }}</th></tr></thead>
                    <tbody>
                        @foreach($postingAttempt->events as $event)
                            <tr>
                                <td>{{ $event->occurred_at?->format('d M Y H:i:s') }}</td>
                                <td>{{ $event->event_type }}</td>
                                <td>{{ $event->from_status ?? '-' }} &rarr; {{ $event->to_status ?? '-' }}</td>
                                <td>{{ $event->actor?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('accounting.error_message') }}</h5></div>
            <div class="card-body">
                <div class="text-danger fw-semibold">{{ $postingAttempt->error_code ?? '-' }}</div>
                <div class="mt-2 text-break">{{ $postingAttempt->error_message ?? __('accounting.no_error_recorded') }}</div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('accounting.source_snapshot') }}</h5></div>
            <div class="card-body"><pre class="small mb-0 text-wrap">{{ json_encode($postingAttempt->source_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
        </div>
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('accounting.posting_snapshot') }}</h5></div>
            <div class="card-body"><pre class="small mb-0 text-wrap">{{ json_encode($postingAttempt->posting_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
        </div>
    </div>
</div>
@endsection
