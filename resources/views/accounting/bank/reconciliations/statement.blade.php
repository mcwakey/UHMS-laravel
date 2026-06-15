@extends('layouts.app')
@section('title', __('accounting.reconciliation_statement'))

@section('content')
@php $adjusted = $reconciliation->statement_closing_balance + $reconciliation->outstanding_deposits_total - $reconciliation->outstanding_withdrawals_total + $reconciliation->adjustments_total; @endphp
<x-page-header :title="__('accounting.reconciliation_statement')" icon="ti-report">
    <a href="{{ route('admin.accounting.bank.reconciliations.show', $reconciliation) }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
</x-page-header>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
            <div>
                <div class="h5 fw-bold mb-0">{{ $reconciliation->bankAccount->name }}</div>
                <div class="text-muted">{{ $reconciliation->bankAccount->bank_name }} · {{ $reconciliation->bankAccount->glAccount?->code }} {{ $reconciliation->bankAccount->glAccount?->name }}</div>
            </div>
            <div class="text-end">
                <div class="fw-semibold">{{ __('accounting.bank_reconciliation') }}</div>
                <div class="text-muted">{{ $reconciliation->period_start->format('d M Y') }} – {{ $reconciliation->period_end->format('d M Y') }}</div>
                <span class="badge bg-{{ ['approved'=>'success','reversed'=>'dark'][$reconciliation->status] ?? 'info' }}">{{ __('statuses.default.' . $reconciliation->status) }}</span>
            </div>
        </div>

        <table class="table">
            <tr><td>{{ __('accounting.closing_statement_balance') }}</td><td class="text-end">&#8373;{{ number_format($reconciliation->statement_closing_balance, 2) }}</td></tr>
            <tr><td>{{ __('common.add') ?? 'Add' }}: {{ __('accounting.outstanding_deposits') }}</td><td class="text-end">&#8373;{{ number_format($reconciliation->outstanding_deposits_total, 2) }}</td></tr>
            <tr><td>{{ __('common.less') ?? 'Less' }}: {{ __('accounting.outstanding_withdrawals') }}</td><td class="text-end">(&#8373;{{ number_format($reconciliation->outstanding_withdrawals_total, 2) }})</td></tr>
            <tr><td>{{ __('accounting.adjustments') }}</td><td class="text-end">&#8373;{{ number_format($reconciliation->adjustments_total, 2) }}</td></tr>
            <tr class="fw-bold border-top"><td>{{ __('accounting.adjusted_statement_balance') }}</td><td class="text-end">&#8373;{{ number_format($adjusted, 2) }}</td></tr>
            <tr><td>{{ __('accounting.book_closing_balance') }}</td><td class="text-end">&#8373;{{ number_format($reconciliation->book_closing_balance, 2) }}</td></tr>
            <tr class="fw-bold border-top {{ abs((float)$reconciliation->difference) > 0.001 ? 'text-danger' : 'text-success' }}"><td>{{ __('accounting.difference') }}</td><td class="text-end">&#8373;{{ number_format($reconciliation->difference, 2) }}</td></tr>
        </table>

        <div class="row mt-4 small text-muted">
            <div class="col-md-6">{{ __('accounting.prepare_reconciliation') }}: {{ $reconciliation->preparedBy?->name ?? '—' }} · {{ optional($reconciliation->prepared_at)->format('d M Y H:i') }}</div>
            <div class="col-md-6 text-md-end">{{ __('accounting.approve_reconciliation') }}: {{ $reconciliation->approvedBy?->name ?? '—' }} · {{ optional($reconciliation->approved_at)->format('d M Y H:i') }}</div>
        </div>
    </div>
</div>
@endsection
