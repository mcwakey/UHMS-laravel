@extends('layouts.app')
@section('title', $journal->journal_number)

@section('content')
<x-page-header :title="$journal->journal_number" icon="ti-journal">
    <x-slot:actions>
        <a href="{{ route('admin.accounting.journals.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
        @if($journal->status->value === 'draft')
            @can('accounting.journals.edit')
                <a href="{{ route('admin.accounting.journals.edit', $journal) }}" class="btn btn-outline-primary"><i class="ti ti-edit me-1"></i>{{ __('common.edit') }}</a>
            @endcan
            @can('accounting.journals.post')
                <x-confirm-form :action="route('admin.accounting.journals.post', $journal)" :button-label="__('accounting.post')" button-class="btn btn-success" icon="ti-circle-check" :confirm-title="__('accounting.post_journal_question')" :confirm-text="__('accounting.posted_journals_cannot_be_edited')" :confirm-button="__('accounting.post')" />
            @endcan
            @can('accounting.journals.cancel')
                <x-confirm-form :action="route('admin.accounting.journals.cancel', $journal)" method="PATCH" :button-label="__('accounting.cancel_draft')" button-class="btn btn-outline-danger" icon="ti-ban" :confirm-title="__('accounting.cancel_draft_question')" :confirm-text="__('accounting.cancelled_drafts_do_not_affect_ledger')" :confirm-button="__('accounting.cancel_draft')" />
            @endcan
        @elseif($journal->status->value === 'posted')
            @can('accounting.journals.reverse')
                <x-confirm-form :action="route('admin.accounting.journals.reverse', $journal)" method="POST" :button-label="__('accounting.reverse')" button-class="btn btn-outline-danger" icon="ti-arrow-back-up" :confirm-title="__('accounting.reverse_journal_question')" :confirm-text="__('accounting.reversal_journal_will_be_posted')" :confirm-button="__('accounting.reverse')" :require-reason="true" reason-name="reason" :reason-placeholder="__('accounting.reverse_reason_placeholder')" />
            @endcan
        @endif
    </x-slot:actions>
</x-page-header>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">{{ __('common.date') }}</div><div class="fw-semibold">{{ $journal->entry_date?->format('d M Y') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">{{ __('common.status') }}</div><span class="badge bg-{{ $journal->status->color() }}">{{ $journal->status->translatedLabel() }}</span></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">{{ __('accounting.period') }}</div><div class="fw-semibold">{{ $journal->accountingPeriod?->name ?? '-' }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">{{ __('accounting.source') }}</div><div class="fw-semibold">{{ $journal->source_module ?: 'MANUAL' }}</div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="text-muted small">{{ __('common.description') }}</div>
                <div>{{ $journal->description }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">{{ __('common.reference') }}</div>
                <div>{{ $journal->reference_number ?? '-' }}</div>
            </div>
            @if($sourceLink)
                <div class="col-md-4">
                    <div class="text-muted small">{{ __('accounting.source_record') }}</div>
                    <a href="{{ $sourceLink['url'] }}">{{ $sourceLink['label'] }}</a>
                </div>
            @endif
            @if($journal->reversedEntry)
                <div class="col-md-6">
                    <div class="text-muted small">{{ __('accounting.reverses') }}</div>
                    <a href="{{ route('admin.accounting.journals.show', $journal->reversedEntry) }}">{{ $journal->reversedEntry->journal_number }}</a>
                </div>
            @endif
            @if($journal->reversal_reason)
                <div class="col-md-6">
                    <div class="text-muted small">{{ __('accounting.reversal_reason') }}</div>
                    <div>{{ $journal->reversal_reason }}</div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('accounting.account') }}</th>
                    <th>{{ __('common.description') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th class="text-end">{{ __('accounting.debit') }}</th>
                    <th class="text-end">{{ __('accounting.credit') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($journal->lines as $line)
                <tr>
                    <td class="fw-semibold">{{ $line->account?->display_name }}</td>
                    <td>{{ $line->description ?: '-' }}</td>
                    <td>{{ $line->department?->name ?? '-' }}</td>
                    <td class="text-end">GH₵ {{ number_format((float) $line->debit, 2) }}</td>
                    <td class="text-end">GH₵ {{ number_format((float) $line->credit, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="3">{{ __('accounting.totals') }}</th>
                    <th class="text-end">GH₵ {{ number_format($journal->total_debit, 2) }}</th>
                    <th class="text-end">GH₵ {{ number_format($journal->total_credit, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
