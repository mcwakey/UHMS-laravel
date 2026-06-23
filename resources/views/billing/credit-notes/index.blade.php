@extends('layouts.app')
@section('title', 'Credit Notes')

@section('content')
<x-page-header title="Credit Notes & Write-offs" icon="ti-receipt-refund">
    <x-slot:actions>
        @if($canCreate)
            <a href="{{ route('admin.billing.credit-notes.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>New Credit Note</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    <div class="col-md-4"><x-stat-card title="Total Credit Notes" value="₵{{ number_format($stats['total_credit_notes'] ?? 0, 2) }}" icon="ti-receipt-refund" variant="info" /></div>
    <div class="col-md-4"><x-stat-card title="Total Write-offs" value="₵{{ number_format($stats['total_write_offs'] ?? 0, 2) }}" icon="ti-eraser" variant="danger" /></div>
    <div class="col-md-4"><x-stat-card title="Active Records" :value="$stats['count'] ?? 0" icon="ti-list-numbers" variant="primary" /></div>
</div>

<x-filter-bar :action="route('admin.billing.credit-notes.index')" :reset-url="route('admin.billing.credit-notes.index')">
    <div class="col-md-4"><label class="form-label small">{{ __('common.search') }}</label><input name="search" class="form-control" placeholder="Credit note #, invoice, patient..." value="{{ request('search') }}"></div>
    <div class="col-md-3">
        <label class="form-label small">Type</label>
        <select name="type" class="form-select">
            <option value="">All Types</option>
            @foreach(\App\Enums\CreditNoteType::cases() as $type)
                <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">{{ __('common.status') }}</label>
        <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="issued" @selected(request('status') === 'issued')>Issued</option>
            <option value="reversed" @selected(request('status') === 'reversed')>Reversed originals</option>
            <option value="reversal" @selected(request('status') === 'reversal')>Reversal records</option>
        </select>
    </div>
</x-filter-bar>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Credit Note #</th><th>Invoice</th><th>Patient</th><th>Type</th><th class="text-end">Amount</th><th>Reason</th><th>Status</th><th>Issued By</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($creditNotes as $cn)
                        @php
                            $canReverse = $cn->status === 'issued'
                                && ! $cn->is_reversal
                                && ($cn->type === \App\Enums\CreditNoteType::WRITE_OFF
                                    ? ((auth()->user()?->can('credit_notes.write_off') ?? false) || (auth()->user()?->can('billing.write_off.reverse') ?? false))
                                    : ((auth()->user()?->can('credit_notes.create') ?? false) || (auth()->user()?->can('billing.credit_note.reverse') ?? false)));
                        @endphp
                        <tr>
                            <td class="fw-medium">{{ $cn->credit_note_number }}</td>
                            <td>@if($cn->invoice)<a href="{{ route('admin.billing.invoices.show', $cn->invoice_id) }}">{{ $cn->invoice->invoice_number }}</a>@else N/A @endif</td>
                            <td><div>{{ $cn->patient ? trim($cn->patient->first_name.' '.$cn->patient->last_name) : 'N/A' }}</div><small class="text-muted">{{ $cn->patient?->patient_number }}</small></td>
                            <td><span class="badge bg-soft-{{ $cn->type?->color() ?? 'secondary' }}">{{ $cn->type?->label() }}</span></td>
                            <td class="text-end">₵{{ number_format($cn->amount, 2) }}</td>
                            <td><small>{{ $cn->reason }}</small></td>
                            <td><span class="badge bg-{{ $cn->status === 'issued' ? 'success' : ($cn->status === 'reversal' ? 'warning' : 'secondary') }}">{{ ucfirst($cn->status) }}</span></td>
                            <td>{{ $cn->issuedBy?->name ?? 'N/A' }}</td>
                            <td>{{ optional($cn->created_at)->format('d M Y') }}</td>
                            <td class="text-end">
                                @if($canReverse)
                                    <form method="POST" action="{{ route('admin.billing.credit-notes.reverse', $cn) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="reason" value="Manual reversal">
                                        <button class="btn btn-sm btn-outline-danger"><i class="ti ti-arrow-back-up me-1"></i>Reverse</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No credit notes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($creditNotes->hasPages())<div class="card-footer">{{ $creditNotes->withQueryString()->links() }}</div>@endif
</div>
@endsection
