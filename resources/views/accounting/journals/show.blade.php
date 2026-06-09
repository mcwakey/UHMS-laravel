@extends('layouts.app')
@section('title', $journal->journal_number)

@section('content')
<x-page-header :title="$journal->journal_number" icon="ti-journal">
    <x-slot:actions>
        <a href="{{ route('admin.accounting.journals.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>Back</a>
        @if($journal->status->value === 'draft')
            @can('accounting.journals.edit')
                <a href="{{ route('admin.accounting.journals.edit', $journal) }}" class="btn btn-outline-primary"><i class="ti ti-edit me-1"></i>Edit</a>
            @endcan
            @can('accounting.journals.post')
                <x-confirm-form :action="route('admin.accounting.journals.post', $journal)" button-label="Post" button-class="btn btn-success" icon="ti-circle-check" confirm-title="Post journal entry?" confirm-text="Posted journal entries cannot be edited directly." confirm-button="Post" />
            @endcan
            @can('accounting.journals.cancel')
                <x-confirm-form :action="route('admin.accounting.journals.cancel', $journal)" method="PATCH" button-label="Cancel Draft" button-class="btn btn-outline-danger" icon="ti-ban" confirm-title="Cancel draft?" confirm-text="Cancelled drafts do not affect the general ledger." confirm-button="Cancel Draft" />
            @endcan
        @elseif($journal->status->value === 'posted')
            @can('accounting.journals.reverse')
                <x-confirm-form :action="route('admin.accounting.journals.reverse', $journal)" method="POST" button-label="Reverse" button-class="btn btn-outline-danger" icon="ti-arrow-back-up" confirm-title="Reverse journal entry?" confirm-text="A reversal journal will be posted with debit and credit swapped." confirm-button="Reverse" :require-reason="true" reason-name="reason" reason-placeholder="Why is this journal being reversed?" />
            @endcan
        @endif
    </x-slot:actions>
</x-page-header>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">Date</div><div class="fw-semibold">{{ $journal->entry_date?->format('d M Y') }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">Status</div><span class="badge bg-{{ $journal->status->color() }}">{{ $journal->status->label() }}</span></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">Period</div><div class="fw-semibold">{{ $journal->accountingPeriod?->name ?? '-' }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="text-muted small">Source</div><div class="fw-semibold">{{ $journal->source_module ?: 'MANUAL' }}</div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="text-muted small">Description</div>
                <div>{{ $journal->description }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Reference</div>
                <div>{{ $journal->reference_number ?? '-' }}</div>
            </div>
            @if($journal->reversedEntry)
                <div class="col-md-6">
                    <div class="text-muted small">Reverses</div>
                    <a href="{{ route('admin.accounting.journals.show', $journal->reversedEntry) }}">{{ $journal->reversedEntry->journal_number }}</a>
                </div>
            @endif
            @if($journal->reversal_reason)
                <div class="col-md-6">
                    <div class="text-muted small">Reversal Reason</div>
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
                    <th>Account</th>
                    <th>Description</th>
                    <th>Department</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
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
                    <th colspan="3">Totals</th>
                    <th class="text-end">GH₵ {{ number_format($journal->total_debit, 2) }}</th>
                    <th class="text-end">GH₵ {{ number_format($journal->total_credit, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
