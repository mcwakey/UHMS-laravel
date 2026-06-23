@extends('layouts.app')
@section('title', 'Patient Statement')

@php $money = fn ($value) => '₵'.number_format((float) ($value ?? 0), 2); @endphp

@section('content')
<x-page-header title="Patient Statement" icon="ti-file-description">
    <x-slot:actions>
        <a href="{{ route('admin.billing.statements.pdf', array_merge(['patient' => $patient], request()->query())) }}" class="btn btn-outline-danger"><i class="ti ti-file-type-pdf me-1"></i>PDF</a>
        <a href="{{ route('admin.billing.statements.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body">
    <div class="row g-3">
        <div class="col-md-4"><div class="text-muted small">{{ __('common.patient') }}</div><div class="fw-semibold">{{ $patient->full_name }}</div><small>{{ $patient->patient_number }}</small></div>
        <div class="col-md-2"><div class="text-muted small">Charges</div><div class="fw-semibold">{{ $money($summary['total_charges'] ?? 0) }}</div></div>
        <div class="col-md-2"><div class="text-muted small">Payments</div><div class="fw-semibold">{{ $money($summary['total_payments'] ?? 0) }}</div></div>
        <div class="col-md-2"><div class="text-muted small">{{ __('billing.balance') }}</div><div class="fw-semibold text-danger">{{ $money($summary['balance_due'] ?? 0) }}</div></div>
    </div>
</div></div>

<x-filter-bar :action="route('admin.billing.statements.show', $patient)" :reset-url="route('admin.billing.statements.show', $patient)">
    <div class="col-md-3"><label class="form-label small">From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
    <div class="col-md-3"><label class="form-label small">To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
</x-filter-bar>

<div class="card"><div class="card-body p-0"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Reference</th><th>Description</th><th class="text-end">Charges</th><th class="text-end">Payments</th><th class="text-end">Balance</th></tr></thead>
        <tbody>
            @forelse($ledger as $entry)
                <tr><td>{{ optional($entry['date'])->format('d M Y') }}</td><td>{{ $entry['type'] }}</td><td>{{ $entry['reference'] }}</td><td>{{ $entry['description'] }}</td><td class="text-end">{{ $money($entry['charges']) }}</td><td class="text-end">{{ $money($entry['payments']) }}</td><td class="text-end">{{ $money($entry['balance']) }}</td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No statement entries found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div></div>
@endsection
