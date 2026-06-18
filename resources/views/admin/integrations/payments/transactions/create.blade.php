@extends('layouts.app')

@section('title', __('payments.gateway.initiate_payment'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('payments.gateway.initiate_payment')" :description="__('payments.gateway.payment_gateway')" icon="ti-cash-register"
            :breadcrumbs="[
                ['label' => __('payments.gateway.payment_transactions'), 'url' => route('admin.integrations.payments.transactions.index')],
                ['label' => __('payments.gateway.initiate_payment')],
            ]" />

        @unless($hasProvider)
            <div class="alert alert-warning"><i class="ti ti-alert-triangle me-1"></i>{{ __('payments.gateway.gateway_disabled') }}</div>
        @endunless

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.integrations.payments.transactions.store') }}">
                    @csrf
                    @if($invoice)
                        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                        <div class="alert alert-info">
                            <i class="ti ti-file-invoice me-1"></i>{{ __('invoices.invoice') }}: <strong>{{ $invoice->invoice_number }}</strong>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('payments.gateway.amount') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $invoice?->balance) }}" class="form-control @error('amount') is-invalid @enderror" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('payments.gateway.method') }} <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="mtn_momo">{{ __('payments.gateway.method_mtn_momo') }}</option>
                                <option value="vodafone_cash">{{ __('payments.gateway.method_telecel_cash') }}</option>
                                <option value="airteltigo_money">{{ __('payments.gateway.method_airteltigo_money') }}</option>
                                <option value="card">{{ __('payments.gateway.method_card') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('payments.gateway.payer_phone') }} <span class="text-danger">*</span></label>
                            <input type="text" name="payer_phone" value="{{ old('payer_phone', $invoice?->patient?->phone) }}" class="form-control @error('payer_phone') is-invalid @enderror" required>
                            @error('payer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.gateway.payer_name') }}</label>
                            <input type="text" name="payer_name" value="{{ old('payer_name', $invoice?->patient?->full_name) }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.gateway.payer_email') }}</label>
                            <input type="email" name="payer_email" value="{{ old('payer_email') }}" class="form-control">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3" @disabled(! $hasProvider)>
                        <i class="ti ti-send me-1"></i>{{ __('payments.gateway.initiate_payment') }}
                    </button>
                    <a href="{{ route('admin.integrations.payments.transactions.index') }}" class="btn btn-light mt-3">{{ __('integrations.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
