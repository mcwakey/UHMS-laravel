@extends('layouts.app')
@section('title', 'Invoice Settings')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('settings.title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">{{ __('settings.invoice_settings_breadcrumb') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card">
            <div class="card-body p-0">
                @include('settings.partials.sidebar')
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('settings.invoice_configuration') }}</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.invoice.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.invoice_prefix_label') }} <span class="text-danger">*</span></label>
                            <input type="text" name="prefix" class="form-control @error('prefix') is-invalid @enderror"
                                   value="{{ old('prefix', $settings['prefix'] ?? 'INV-') }}" required>
                            @error('prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">{{ __('settings.invoice_prefix_help') }}</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.due_days') }} <span class="text-danger">*</span></label>
                            <input type="number" name="due_days" class="form-control @error('due_days') is-invalid @enderror"
                                   value="{{ old('due_days', $settings['due_days'] ?? '30') }}" min="1" max="365" required>
                            @error('due_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">{{ __('settings.due_days_help') }}</small>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h6 class="fw-bold mb-3">{{ __('settings.tax_configuration') }}</h6>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="tax_enabled" value="1"
                                       id="taxEnabled" {{ old('tax_enabled', $settings['tax_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="taxEnabled">{{ __('settings.tax_enabled') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.tax_rate') }}</label>
                            <input type="number" name="tax_rate" class="form-control @error('tax_rate') is-invalid @enderror"
                                   value="{{ old('tax_rate', $settings['tax_rate'] ?? '0') }}" step="0.01" min="0" max="100">
                            @error('tax_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.tax_label') }}</label>
                            <input type="text" name="tax_label" class="form-control"
                                   value="{{ old('tax_label', $settings['tax_label'] ?? 'VAT') }}">
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="mb-3">
                        <label class="form-label">{{ __('settings.invoice_footer_note') }}</label>
                        <textarea name="footer_note" class="form-control" rows="2">{{ old('footer_note', $settings['footer_note'] ?? '') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('settings.terms_conditions') }}</label>
                        <textarea name="terms" class="form-control" rows="4">{{ old('terms', $settings['terms'] ?? '') }}</textarea>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('settings.save_changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
