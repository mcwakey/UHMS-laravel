@extends('layouts.app')
@section('title', __('accounting.failed_posting'))

@section('content')
<x-page-header :title="__('accounting.failed_posting').' #'.$attempt->id" :description="$attempt->idempotency_key" icon="ti-alert-triangle">
    <x-slot:actions><a class="btn btn-outline-secondary" href="{{ route('admin.accounting.failed-postings.index') }}">{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row g-3">
<div class="col-lg-8">
    <div class="card mb-3"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">{{ __('accounting.source_identity') }}</h5><span class="badge bg-{{ ['failed'=>'danger','posted'=>'success','waived'=>'secondary','resolved'=>'info','reversed'=>'dark'][$attempt->status] ?? 'warning' }}">{{ __('accounting.attempt_status_'.$attempt->status) }}</span></div><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-md-3">{{ __('accounting.source_module') }}</dt><dd class="col-md-9">{{ $attempt->source_module }}</dd>
            <dt class="col-md-3">{{ __('accounting.source_record') }}</dt><dd class="col-md-9">@if($sourceLink)<a href="{{ $sourceLink['url'] }}">{{ $sourceLink['label'] }}</a>@else {{ $attempt->source_type }} #{{ $attempt->source_id }}@endif</dd>
            <dt class="col-md-3">{{ __('accounting.posting_type') }}</dt><dd class="col-md-9">{{ $attempt->posting_type }} v{{ $attempt->posting_version }}</dd>
            <dt class="col-md-3">{{ __('accounting.idempotency_key') }}</dt><dd class="col-md-9 text-break"><code>{{ $attempt->idempotency_key }}</code></dd>
            <dt class="col-md-3">{{ __('accounting.attempt_count') }}</dt><dd class="col-md-9">{{ $attempt->attempt_count }}</dd>
            <dt class="col-md-3">{{ __('accounting.first_attempted_at') }}</dt><dd class="col-md-9">{{ $attempt->first_attempted_at?->format('d M Y H:i:s') ?? '-' }}</dd>
            <dt class="col-md-3">{{ __('accounting.last_attempted_at') }}</dt><dd class="col-md-9">{{ $attempt->last_attempted_at?->format('d M Y H:i:s') ?? '-' }}</dd>
            <dt class="col-md-3">{{ __('accounting.next_retry_at') }}</dt><dd class="col-md-9">{{ $attempt->next_retry_at?->format('d M Y H:i:s') ?? '-' }}</dd>
            <dt class="col-md-3">{{ __('accounting.journal') }}</dt><dd class="col-md-9">@if($attempt->journalEntry)<a href="{{ route('admin.accounting.journals.show', $attempt->journalEntry) }}">{{ $attempt->journalEntry->journal_number }}</a>@else - @endif</dd>
            <dt class="col-md-3">{{ __('accounting.reversal_journal') }}</dt><dd class="col-md-9">@if($attempt->reversalJournalEntry)<a href="{{ route('admin.accounting.journals.show', $attempt->reversalJournalEntry) }}">{{ $attempt->reversalJournalEntry->journal_number }}</a>@else - @endif</dd>
        </dl>
    </div></div>

    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">{{ __('accounting.attempt_timeline') }}</h5></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>{{ __('common.date') }}</th><th>{{ __('accounting.event') }}</th><th>{{ __('common.status') }}</th><th>{{ __('accounting.error_message') }}</th><th>{{ __('accounting.user') }}</th></tr></thead><tbody>
    @forelse($attempt->events as $event)<tr><td>{{ $event->occurred_at?->format('d M Y H:i:s') }}</td><td>{{ $event->event_type }}</td><td>{{ $event->from_status ?? '-' }} &rarr; {{ $event->to_status ?? '-' }}</td><td>{{ $event->error_message ?? '-' }}</td><td>{{ $event->actor?->name ?? '-' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">{{ __('accounting.no_posting_attempts') }}</td></tr>@endforelse
    </tbody></table></div></div>

    @if($attempt->resolved_at)
    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">{{ __('accounting.resolution_details') }}</h5></div><div class="card-body"><dl class="row mb-0">
        <dt class="col-md-3">{{ __('accounting.resolution_type') }}</dt><dd class="col-md-9">{{ $attempt->resolution_type }}</dd>
        <dt class="col-md-3">{{ __('accounting.resolution_note') }}</dt><dd class="col-md-9">{{ $attempt->resolution_note }}</dd>
        <dt class="col-md-3">{{ __('accounting.resolution_evidence') }}</dt><dd class="col-md-9">{{ $attempt->resolution_evidence ?: $attempt->resolution_reference ?: '-' }}</dd>
        <dt class="col-md-3">{{ __('accounting.materiality_note') }}</dt><dd class="col-md-9">{{ $attempt->materiality_note ?: '-' }}</dd>
        <dt class="col-md-3">{{ __('accounting.resolved_by') }}</dt><dd class="col-md-9">{{ $attempt->resolvedBy?->name ?? '-' }} · {{ $attempt->resolved_at?->format('d M Y H:i') }}</dd>
        <dt class="col-md-3">{{ __('accounting.corrective_journal') }}</dt><dd class="col-md-9">@if($attempt->resolutionJournalEntry)<a href="{{ route('admin.accounting.journals.show', $attempt->resolutionJournalEntry) }}">{{ $attempt->resolutionJournalEntry->journal_number }}</a>@else - @endif</dd>
    </dl></div></div>
    @endif
</div>

<div class="col-lg-4">
    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">{{ __('accounting.error_message') }}</h5></div><div class="card-body"><div class="text-danger fw-semibold">{{ $attempt->error_code ?? '-' }}</div><div class="mt-2 text-break">{{ $attempt->error_message ?? __('accounting.no_error_recorded') }}</div></div></div>
    @foreach([['error_context',$errorContext],['source_snapshot',$sourceSnapshot],['posting_snapshot',$postingSnapshot]] as [$label,$value])
    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">{{ __('accounting.'.$label) }}</h5></div><div class="card-body"><pre class="small mb-0 text-wrap" style="max-height:320px;overflow:auto">{{ $value ?: '-' }}</pre></div></div>
    @endforeach

    @if($attempt->status === 'failed')
    @can('accounting.failed_postings.retry')
    <div class="card mb-3"><div class="card-body"><form method="POST" action="{{ route('admin.accounting.failed-postings.retry', $attempt) }}">@csrf<button class="btn btn-warning w-100"><i class="ti ti-refresh me-1"></i>{{ __('accounting.retry_posting') }}</button>@if(!$supported)<div class="small text-danger mt-2">{{ __('accounting.unsupported_source_type') }}</div>@endif</form></div></div>
    @endcan
    @can('accounting.failed_postings.resolve')
    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">{{ __('accounting.resolve_posting') }}</h5></div><div class="card-body"><form method="POST" action="{{ route('admin.accounting.failed-postings.resolve', $attempt) }}">@csrf
        <label class="form-label">{{ __('accounting.resolution_type') }}</label><select class="form-select mb-2" name="resolution_type" required>@foreach($resolutionTypes as $type)<option value="{{ $type }}">{{ __('accounting.'.$type) }}</option>@endforeach</select>
        <label class="form-label">{{ __('accounting.resolution_note') }}</label><textarea class="form-control mb-2" name="resolution_note" required></textarea>
        <label class="form-label">{{ __('accounting.corrective_journal') }}</label><select class="form-select mb-2" name="resolution_journal_entry_id"><option value="">-</option>@foreach($journals as $journal)<option value="{{ $journal->id }}">{{ $journal->journal_number }} - {{ Str::limit($journal->description, 45) }}</option>@endforeach</select>
        <label class="form-label">{{ __('accounting.evidence_reference') }}</label><input class="form-control mb-2" name="resolution_reference">
        <label class="form-label">{{ __('accounting.resolution_evidence') }}</label><textarea class="form-control mb-2" name="resolution_evidence"></textarea>
        <label class="form-label">{{ __('accounting.linked_source_type') }}</label><input class="form-control mb-2" name="resolution_source_type">
        <label class="form-label">{{ __('accounting.linked_source_id') }}</label><input type="number" min="1" class="form-control mb-2" name="resolution_source_id">
        <button class="btn btn-info w-100">{{ __('accounting.resolve_posting') }}</button>
    </form></div></div>
    @endcan
    @can('accounting.failed_postings.waive')
    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">{{ __('accounting.waive_posting') }}</h5></div><div class="card-body"><form method="POST" action="{{ route('admin.accounting.failed-postings.waive', $attempt) }}">@csrf
        <label class="form-label">{{ __('accounting.waive_reason') }}</label><textarea class="form-control mb-2" name="waive_reason" required></textarea>
        <label class="form-label">{{ __('accounting.materiality_note') }}</label><textarea class="form-control mb-2" name="materiality_note" required></textarea>
        <label class="form-label">{{ __('accounting.waiver_review_date') }}</label><input type="date" class="form-control mb-2" name="waiver_review_date">
        <label class="form-label">{{ __('accounting.evidence_reference') }}</label><input class="form-control mb-2" name="resolution_reference">
        <button class="btn btn-outline-danger w-100">{{ __('accounting.waive_posting') }}</button>
    </form></div></div>
    @endcan
    @endif
</div>
</div>
@endsection
