@extends('layouts.app')
@section('title', __('accounting.record_type', ['type' => __('statuses.default.' . $type)]))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('accounting.record_type', ['type' => __('statuses.default.' . $type)]) }}</h4>
    </div>
    <div>
        <a href="{{ route($type === 'income' ? 'admin.accounts.income.index' : 'admin.accounts.expenses.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('accounting.type_details', ['type' => __('statuses.default.' . $type)]) }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.accounts.entries.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select select2" required>
                                <option value="">{{ __('accounting.select_category') }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', date('Y-m-d')) }}" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Amount (GH₵) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="">{{ __('accounting.select_method') }}</option>
                                @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->value }}" {{ old('payment_method') == $pm->value ? 'selected' : '' }}>{{ $pm->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Receipt / Reference #</label>
                            <input type="text" name="receipt_number" class="form-control" value="{{ old('receipt_number') }}" placeholder="Receipt number">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}" placeholder="External reference (optional)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe the {{ $type }}..." required>{{ old('description') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-{{ $type === 'income' ? 'trending-up' : 'trending-down' }} me-1"></i>{{ __('accounting.record_type', ['type' => __('statuses.default.' . $type)]) }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Guidelines</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @if($type === 'expense')
                    <li class="mb-2"><i class="ti ti-circle-check text-success me-1"></i> Record all outgoing payments</li>
                    <li class="mb-2"><i class="ti ti-circle-check text-success me-1"></i> Include receipt numbers when available</li>
                    <li class="mb-2"><i class="ti ti-circle-check text-success me-1"></i> Choose the correct category</li>
                    <li><i class="ti ti-info-circle text-info me-1"></i> Entries require approval before finalizing</li>
                    @else
                    <li class="mb-2"><i class="ti ti-circle-check text-success me-1"></i> Record non-invoice income here</li>
                    <li class="mb-2"><i class="ti ti-circle-check text-success me-1"></i> Patient payments are recorded via billing</li>
                    <li class="mb-2"><i class="ti ti-circle-check text-success me-1"></i> Include source reference</li>
                    <li><i class="ti ti-info-circle text-info me-1"></i> Entries require approval before finalizing</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
