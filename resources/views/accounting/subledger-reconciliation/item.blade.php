@extends('layouts.app')
@section('title', __('accounting.reconciliation_item').' #'.$item->id)
@section('content')
<x-page-header :title="__('accounting.reconciliation_item').' #'.$item->id" :description="$item->source_reference ?: $item->source_type" icon="ti-list-details">
    <x-slot:actions><a class="btn btn-outline-secondary" href="{{ route('admin.accounting.subledger-reconciliation.show', $run) }}">{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-3 mb-4">
<div class="col-md-7"><div class="card h-100"><div class="card-header"><h5 class="mb-0">{{ __('accounting.reconciliation_item_detail') }}</h5></div><div class="card-body row g-3">
    <div class="col-md-6"><span class="text-muted">{{ __('accounting.source_type') }}</span><div>{{ $item->source_type }}</div></div>
    <div class="col-md-6"><span class="text-muted">{{ __('accounting.source_record') }}</span><div>{{ $item->source_reference ?? '#'.$item->source_id }}</div></div>
    <div class="col-md-6"><span class="text-muted">{{ __('accounting.control_account') }}</span><div>@if($item->glAccount)<a href="{{ route('admin.accounting.general-ledger', ['account_id' => $item->gl_account_id]) }}">{{ $item->glAccount->display_name }}</a>@else - @endif</div></div>
    <div class="col-md-6"><span class="text-muted">{{ __('accounting.classification') }}</span><div>{{ __('accounting.classification_'.$item->classification) }}</div></div>
    <div class="col-md-4"><span class="text-muted">{{ __('accounting.subledger_total') }}</span><div class="fs-4">{{ number_format((float)$item->subledger_amount, 2) }}</div></div>
    <div class="col-md-4"><span class="text-muted">{{ __('accounting.gl_total') }}</span><div class="fs-4">{{ number_format((float)$item->gl_amount, 2) }}</div></div>
    <div class="col-md-4"><span class="text-muted">{{ __('accounting.difference_amount') }}</span><div class="fs-4">{{ number_format((float)$item->difference_amount, 2) }}</div></div>
</div></div></div>
<div class="col-md-5"><div class="card h-100"><div class="card-header"><h5 class="mb-0">{{ __('accounting.snapshot') }}</h5></div><div class="card-body"><pre class="small bg-light p-3 rounded mb-0" style="max-height:360px;overflow:auto">{{ json_encode($item->metadata_snapshot, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></div></div></div>
</div>

<div class="card mb-4"><div class="card-header"><h5 class="mb-0">{{ __('accounting.resolution_history') }}</h5></div><div class="card-body">
@forelse($item->resolutions as $resolution)
<div class="border-bottom pb-3 mb-3"><div class="d-flex justify-content-between"><strong>{{ __('accounting.resolution_type_'.$resolution->resolution_type) }}</strong><small class="text-muted">{{ $resolution->resolved_at?->format('d M Y H:i') }} · {{ $resolution->resolvedBy?->name }}</small></div><div>{{ $resolution->resolution_note }}</div>
@if($resolution->linkedJournalEntry)<a href="{{ route('admin.accounting.journals.show', $resolution->linkedJournalEntry) }}">{{ $resolution->linkedJournalEntry->journal_number }}</a>@endif
@if($resolution->linkedPostingAttempt)<a class="ms-2" href="{{ route('admin.accounting.failed-postings.show', $resolution->linkedPostingAttempt) }}">{{ __('accounting.posting_attempt') }} #{{ $resolution->linkedPostingAttempt->id }}</a>@endif
</div>
@empty<div class="text-muted">{{ __('accounting.no_resolutions') }}</div>@endforelse
</div></div>

@can('accounting.subledger_reconciliation.resolve')
<div class="card"><div class="card-header"><h5 class="mb-0">{{ __('accounting.add_resolution') }}</h5></div><div class="card-body">
<form method="POST" action="{{ route('admin.accounting.subledger-reconciliation.items.resolve', [$run, $item]) }}" class="row g-3">@csrf
    <div class="col-md-4"><label class="form-label">{{ __('accounting.resolution_type') }}</label><select class="form-select" name="resolution_type" required>@foreach($resolutionTypes as $type)<option value="{{ $type }}">{{ __('accounting.resolution_type_'.$type) }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">{{ __('accounting.linked_journal') }}</label><select class="form-select" name="linked_journal_entry_id"><option value="">{{ __('common.none') }}</option>@foreach($journals as $journal)<option value="{{ $journal->id }}">{{ $journal->journal_number }} - {{ Str::limit($journal->description, 45) }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">{{ __('accounting.linked_posting_attempt') }}</label><select class="form-select" name="linked_posting_attempt_id"><option value="">{{ __('common.none') }}</option>@foreach($postingAttempts as $attempt)<option value="{{ $attempt->id }}">#{{ $attempt->id }} {{ $attempt->source_type }} #{{ $attempt->source_id }} ({{ $attempt->status }})</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label">{{ __('accounting.resolution_note') }}</label><textarea class="form-control" name="resolution_note" rows="3" required></textarea></div>
    <div class="col-md-6"><label class="form-label">{{ __('accounting.linked_source_type') }}</label><input class="form-control" name="linked_source_type"></div><div class="col-md-3"><label class="form-label">{{ __('accounting.linked_source_id') }}</label><input type="number" min="1" class="form-control" name="linked_source_id"></div>
    <div class="col-12"><button class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('accounting.add_resolution') }}</button></div>
</form></div></div>
@endcan
@endsection
