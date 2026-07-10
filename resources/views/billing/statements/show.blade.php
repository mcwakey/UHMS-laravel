@extends('layouts.app')
@section('title', __('billing.patient_statement_title'))

@php $money = fn ($value) => '₵'.number_format((float) ($value ?? 0), 2); @endphp

@section('content')
<x-page-header :title="__('billing.patient_statement_title')" icon="ti-file-description">
    <x-slot:actions>
        <a href="{{ route('admin.billing.statements.pdf', array_merge(['patient' => $patient], request()->query())) }}" class="btn btn-outline-danger"><i class="ti ti-file-type-pdf me-1"></i>{{ __('reports.export_pdf') }}</a>
        <a href="{{ route('admin.billing.statements.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body">
    <div class="row g-3">
        <div class="col-md-4"><div class="text-muted small">{{ __('common.patient') }}</div><div class="fw-semibold">{{ $patient->full_name }}</div><small>{{ $patient->patient_number }}</small></div>
        <div class="col-md-2"><div class="text-muted small">{{ __('billing.ledger_charges') }}</div><div class="fw-semibold">{{ $money($summary['total_charges'] ?? 0) }}</div></div>
        <div class="col-md-2"><div class="text-muted small">{{ __('billing.ledger_payments') }}</div><div class="fw-semibold">{{ $money($summary['total_payments'] ?? 0) }}</div></div>
        <div class="col-md-2"><div class="text-muted small">{{ __('billing.balance') }}</div><div class="fw-semibold text-danger">{{ $money($summary['balance_due'] ?? 0) }}</div></div>
    </div>
</div></div>

@php
    $pbSummary = app(\App\Services\Billing\PatientOutstandingBalanceService::class)->buildPatientBalanceSummary($patient);
@endphp
<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <x-stat-card :title="__('billing.previous_visits_outstanding')" :value="$pbSummary['previous_outstanding']" format="currency" variant="warning" icon="ti-history" />
    </div>
    <div class="col-md-3 col-6">
        <x-stat-card :title="__('billing.total_patient_outstanding')" :value="$pbSummary['total_outstanding']" format="currency" variant="danger" icon="ti-report-money" />
    </div>
    <div class="col-md-3 col-6">
        <x-stat-card :title="__('billing.oldest_unpaid_invoice')"
            :value="$pbSummary['oldest_unpaid_invoice']?->invoice_number ?? '—'"
            :subtitle="$pbSummary['oldest_age_days'] !== null ? __('billing.age_days', ['days' => $pbSummary['oldest_age_days']]) : null"
            variant="secondary" icon="ti-file-invoice" />
    </div>
    <div class="col-md-3 col-6">
        <x-stat-card :title="__('billing.aging_bucket')" :value="$pbSummary['ar_bucket'] ?? '—'" variant="info" icon="ti-calendar-stats" />
    </div>
</div>

<x-filter-bar :action="route('admin.billing.statements.show', $patient)" :reset-url="route('admin.billing.statements.show', $patient)">
    <div class="col-md-3"><label class="form-label small">{{ __('common.from') }}</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
    <div class="col-md-3"><label class="form-label small">{{ __('common.to') }}</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
</x-filter-bar>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>{{ __('common.date') }}</th><th>{{ __('common.type') }}</th><th>{{ __('billing.ledger_reference') }}</th><th>{{ __('billing.ledger_description') }}</th><th class="text-end">{{ __('billing.ledger_charges') }}</th><th class="text-end">{{ __('billing.ledger_payments') }}</th><th class="text-end">{{ __('billing.ledger_balance') }}</th></tr></thead>
        <tbody>
            @forelse($ledger as $entry)
                <tr><td>{{ optional($entry['date'])->format('d M Y') }}</td><td>{{ $entry['type'] }}</td><td>{{ $entry['reference'] }}</td><td>{{ $entry['description'] }}</td><td class="text-end">{{ $money($entry['charges']) }}</td><td class="text-end">{{ $money($entry['payments']) }}</td><td class="text-end">{{ $money($entry['balance']) }}</td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('billing.no_statement_entries_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div></div>
@endsection
