@extends('layouts.app')
@section('title', 'Organization Settings')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('settings.title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">{{ __('settings.org_settings_breadcrumb') }}</li>
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
                <h5 class="card-title mb-0">{{ __('settings.org_information') }}</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.organization.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @if(!empty($settings['logo']))
                    <div class="mb-3">
                        <label class="form-label">{{ __('settings.current_logo') }}</label>
                        <div>
                            <img src="{{ asset('storage/' . $settings['logo']) }}" alt="Logo" class="img-thumbnail" style="max-height: 80px;">
                        </div>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.company_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $settings['name'] ?? '') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.email_address') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $settings['email'] ?? '') }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.org_phone') }}</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $settings['phone'] ?? '') }}">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.org_website') }}</label>
                            <input type="url" name="website" class="form-control @error('website') is-invalid @enderror"
                                   value="{{ old('website', $settings['website'] ?? '') }}">
                            @error('website') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('settings.org_address') }}</label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $settings['address'] ?? '') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.city') }}</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $settings['city'] ?? '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.region') }}</label>
                            <input type="text" name="region" class="form-control" value="{{ old('region', $settings['region'] ?? '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.postal_code') }}</label>
                            <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $settings['postal_code'] ?? '') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('settings.org_logo') }}</label>
                        <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                        @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">{{ __('settings.logo_size_help') }}</small>
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
