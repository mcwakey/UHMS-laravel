@extends('layouts.app')
@section('title', __('billing.credit_notes'))

@section('content')
<x-page-header :title="__('billing.credit_notes_write_offs')" icon="ti-receipt-refund">
    <x-slot:actions>
        @if($canCreate)
            <a href="{{ route('admin.billing.credit-notes.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>{{ __('billing.new_credit_note') }}
            </a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    <div class="col-md-4"><x-stat-card :title="__('billing.total_credit_notes')" value="₵{{ number_format($stats['total_credit_notes'] ?? 0, 2) }}" icon="ti-receipt-refund" variant="info" /></div>
    <div class="col-md-4"><x-stat-card :title="__('billing.total_write_offs')" value="₵{{ number_format($stats['total_write_offs'] ?? 0, 2) }}" icon="ti-eraser" variant="danger" /></div>
    <div class="col-md-4"><x-stat-card :title="__('billing.active_records')" :value="$stats['count'] ?? 0" icon="ti-list-numbers" variant="primary" /></div>
</div>

<x-filter-bar :action="route('admin.billing.credit-notes.index')" :reset-url="route('admin.billing.credit-notes.index')">
    <div class="col-md-4">
        <label class="form-label small">{{ __('common.search') }}</label>
        <input name="search" class="form-control" placeholder="{{ __('billing.credit_note_search_placeholder') }}" value="{{ request('search') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small">{{ __('common.type') }}</label>
        <select name="type" class="form-select">
            <option value="">{{ __('billing.all_types') }}</option>
            @foreach(\App\Enums\CreditNoteType::cases() as $type)
                <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">{{ __('common.status') }}</label>
        <select name="status" class="form-select">
            <option value="">{{ __('billing.all_statuses') }}</option>
            <option value="issued" @selected(request('status') === 'issued')>{{ __('billing.issued') }}</option>
            <option value="reversed" @selected(request('status') === 'reversed')>{{ __('billing.reversed_originals') }}</option>
            <option value="reversal" @selected(request('status') === 'reversal')>{{ __('billing.reversal_records') }}</option>
        </select>
    </div>
</x-filter-bar>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('billing.credit_note_number_short') }}</th>
                        <th>{{ __('common.invoice') }}</th>
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th class="text-end">{{ __('common.amount') }}</th>
                        <th>{{ __('common.reason') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('billing.issued_by') }}</th>
                        <th>{{ __('common.date') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
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
                            <td>@if($cn->invoice)<a href="{{ route('admin.billing.invoices.show', $cn->invoice_id) }}">{{ $cn->invoice->invoice_number }}</a>@else {{ __('common.not_available') }} @endif</td>
                            <td><div>{{ $cn->patient ? trim($cn->patient->first_name.' '.$cn->patient->last_name) : __('common.not_available') }}</div><small class="text-muted">{{ $cn->patient?->patient_number }}</small></td>
                            <td><span class="badge bg-soft-{{ $cn->type?->color() ?? 'secondary' }}">{{ $cn->type?->label() }}</span></td>
                            <td class="text-end">₵{{ number_format($cn->amount, 2) }}</td>
                            <td><small>{{ $cn->reason }}</small></td>
                            <td><span class="badge bg-{{ $cn->status === 'issued' ? 'success' : ($cn->status === 'reversal' ? 'warning' : 'secondary') }}">{{ ucfirst($cn->status) }}</span></td>
                            <td>{{ $cn->issuedBy?->name ?? __('common.not_available') }}</td>
                            <td>{{ optional($cn->created_at)->format('d M Y') }}</td>
                            <td class="text-end">
                                @if($canReverse)
                                    <form method="POST" action="{{ route('admin.billing.credit-notes.reverse', $cn) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="reason" value="{{ __('billing.manual_reversal') }}">
                                        <button class="btn btn-sm btn-outline-danger"><i class="ti ti-arrow-back-up me-1"></i>{{ __('billing.reverse') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">{{ __('billing.no_credit_notes_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($creditNotes->hasPages())<div class="card-footer">{{ $creditNotes->withQueryString()->links() }}</div>@endif
</div>
@endsection
