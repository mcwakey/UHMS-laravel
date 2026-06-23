@extends('layouts.app')
@section('title', 'AR Aging')

@php $money = fn ($value) => '₵'.number_format((float) ($value ?? 0), 2); @endphp

@section('content')
<x-page-header title="Accounts Receivable Aging" icon="ti-clock-dollar">
    <x-slot:actions><a href="{{ route('admin.billing.reports.aging.pdf', request()->query()) }}" class="btn btn-outline-danger"><i class="ti ti-file-type-pdf me-1"></i>Export PDF</a></x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    @foreach($aging['buckets'] ?? [] as $bucket)
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card"><div class="card-body">
                <h5 class="mb-1">{{ $money($bucket['total'] ?? 0) }}</h5>
                <p class="text-muted mb-0">{{ $bucket['label'] ?? '' }} <span class="badge bg-light text-dark">{{ $bucket['count'] ?? 0 }}</span></p>
            </div></div>
        </div>
    @endforeach
</div>

<x-filter-bar :action="route('admin.billing.reports.aging')" :reset-url="route('admin.billing.reports.aging')">
    <div class="col-md-2"><label class="form-label small">Payer Type</label><select name="payer_type" class="form-select"><option value="">All Payers</option><option value="patient" @selected(($filters['payer_type'] ?? '') === 'patient')>{{ __('common.patient') }}</option><option value="insurance" @selected(($filters['payer_type'] ?? '') === 'insurance')>{{ __('common.billing_type_insurance') }}</option><option value="sponsor" @selected(($filters['payer_type'] ?? '') === 'sponsor')>{{ __('reports.insurance.sponsor') }}</option><option value="corporate" @selected(($filters['payer_type'] ?? '') === 'corporate')>{{ __('common.billing_type_corporate') }}</option></select></div>
    <div class="col-md-2"><label class="form-label small">{{ __('common.status') }}</label><select name="status" class="form-select"><option value="">Open Statuses</option><option value="pending" @selected(($filters['status'] ?? '') === 'pending')>{{ __('reports.statuses.pending') }}</option><option value="partially_paid" @selected(($filters['status'] ?? '') === 'partially_paid')>{{ __('reports.statuses.partially_paid') }}</option><option value="overdue" @selected(($filters['status'] ?? '') === 'overdue')>{{ __('reports.statuses.overdue') }}</option></select></div>
    <div class="col-md-2"><label class="form-label small">Sponsor</label><select name="sponsor_id" class="form-select"><option value="">All Sponsors</option>@foreach($sponsors as $sponsor)<option value="{{ $sponsor->id }}" @selected((string) ($filters['sponsor_id'] ?? '') === (string) $sponsor->id)>{{ $sponsor->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label small">Insurance</label><select name="insurance_provider_id" class="form-select"><option value="">All Providers</option>@foreach($insuranceProviders as $provider)<option value="{{ $provider->id }}" @selected((string) ($filters['insurance_provider_id'] ?? '') === (string) $provider->id)>{{ $provider->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label small">Corporate</label><select name="corporate_client_id" class="form-select"><option value="">All Clients</option>@foreach($corporateClients as $client)<option value="{{ $client->id }}" @selected((string) ($filters['corporate_client_id'] ?? '') === (string) $client->id)>{{ $client->name }}</option>@endforeach</select></div>
    <div class="col-md-1"><label class="form-label small">As Of</label><input type="date" name="as_of" class="form-control" value="{{ $filters['as_of'] ?? ($aging['as_of'] ?? '') }}"></div>
</x-filter-bar>

<div class="card">
    <div class="card-header d-flex justify-content-between"><h6 class="mb-0">Open Receivables ({{ $aging['grand_count'] ?? 0 }})</h6><strong class="text-danger">Total: {{ $money($aging['grand_total'] ?? 0) }}</strong></div>
    <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Invoice</th><th>Payer</th><th>{{ __('common.patient') }}</th><th>{{ __('common.status') }}</th><th>Due Date</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
                @forelse($aging['rows'] ?? [] as $row)
                    <tr><td>{{ $row['invoice_number'] ?? '' }}</td><td>{{ $row['payer_name'] ?? ($row['payer_type'] ?? '') }}</td><td>{{ $row['patient_name'] ?? '' }}</td><td>{{ $row['status'] ?? '' }}</td><td>{{ $row['due_date'] ?? '' }}</td><td class="text-end">{{ $money($row['balance'] ?? 0) }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No open receivables found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
