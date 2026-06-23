@extends('layouts.app')
@section('title', __('billing.edit_invoice'))

@section('content')
<x-page-header :title="__('billing.edit_invoice')" icon="ti-file-invoice">
    <x-slot:actions>
        <a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}
        </a>
    </x-slot:actions>
</x-page-header>

<div class="card">
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="text-muted small">{{ __('billing.invoice_number') }}</div>
                <div class="fw-semibold">{{ $invoice->invoice_number }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">{{ __('common.patient') }}</div>
                <div class="fw-semibold">{{ $invoice->patient?->full_name ?? 'N/A' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">{{ __('common.total') }}</div>
                <div class="fw-semibold">&#8373;{{ number_format($invoice->total_amount, 2) }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">{{ __('common.balance') }}</div>
                <div class="fw-semibold">&#8373;{{ number_format($invoice->balance, 2) }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.billing.invoices.update', $invoice) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('billing.billing_type') }}</label>
                    <select name="billing_type" class="form-select" required>
                        @foreach($billingTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('billing_type', $invoice->billing_type?->value) === $type->value)>
                                {{ method_exists($type, 'translatedLabel') ? $type->translatedLabel() : $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('reports.insurance.sponsor') }}</label>
                    <select name="sponsor_id" class="form-select">
                        <option value="">{{ __('common.none') }}</option>
                        @foreach($sponsors as $sponsor)
                            <option value="{{ $sponsor->id }}" @selected((string) old('sponsor_id', $invoice->sponsor_id) === (string) $sponsor->id)>{{ $sponsor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('common.billing_type_corporate') }}</label>
                    <select name="corporate_client_id" class="form-select">
                        <option value="">{{ __('common.none') }}</option>
                        @foreach($corporateClients as $client)
                            <option value="{{ $client->id }}" @selected((string) old('corporate_client_id', $invoice->corporate_client_id) === (string) $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('billing.tax_amount') }}</label>
                    <input type="number" step="0.01" min="0" name="tax_amount" class="form-control" value="{{ old('tax_amount', $invoice->tax_amount) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('billing.due_date') }}</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('common.notes') }}</label>
                    <textarea name="notes" class="form-control" rows="4">{{ old('notes', $invoice->notes) }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
