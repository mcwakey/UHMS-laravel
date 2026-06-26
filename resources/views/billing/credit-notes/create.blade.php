@extends('layouts.app')
@section('title', __('billing.new_credit_note'))

@section('content')
<x-page-header :title="__('billing.issue_credit_note_write_off')" icon="ti-receipt-refund">
    <x-slot:actions><a href="{{ route('admin.billing.credit-notes.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <form method="POST" action="{{ route('admin.billing.credit-notes.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ __('common.invoice') }} <span class="text-danger">*</span></label>
                    <select name="invoice_id" class="form-select" required>
                        <option value="">{{ __('billing.select_an_open_invoice') }}</option>
                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice['id'] }}" @selected((string) old('invoice_id', $selected['id'] ?? '') === (string) $invoice['id'])>
                                {{ $invoice['invoice_number'] }} - {{ $invoice['patient_name'] }} (₵{{ number_format($invoice['balance'], 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($selected)
                    <div class="alert alert-info py-2">{{ __('billing.available_to_credit') }}: <strong>₵{{ number_format($selected['available'], 2) }}</strong></div>
                @endif
                <div class="mb-3">
                    <label class="form-label">{{ __('common.type') }} <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        @foreach($typeOptions as $type)
                            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">{{ __('common.amount') }} <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-text">₵</span><input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required></div></div>
                <div class="mb-3"><label class="form-label">{{ __('common.reason') }} <span class="text-danger">*</span></label><input name="reason" class="form-control" maxlength="255" value="{{ old('reason') }}" required></div>
                <div class="mb-3"><label class="form-label">{{ __('common.notes') }}</label><textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea></div>
                <div class="d-flex justify-content-end gap-2"><a href="{{ route('admin.billing.credit-notes.index') }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a><button class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('billing.issue') }}</button></div>
            </form>
        </div></div>
    </div>
</div>
@endsection
