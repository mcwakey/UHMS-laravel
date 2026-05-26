@extends('layouts.app')
@section('title', 'Ward & Admissions Settings')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Settings</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Ward & Admissions Settings</li>
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
                <h5 class="card-title mb-0"><i class="ti ti-bed me-2 text-primary"></i>Ward & Admissions Configuration</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="ti ti-circle-check me-1"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.ward.update') }}">
                    @csrf
                    @method('PUT')

                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Default Fee Services</h6>
                    <p class="text-muted small mb-3">
                        These services are pre-selected on the New Admission form. Staff can still change them per admission.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Admission Fee Service</label>
                            <select name="admission_fee_service_id" class="form-select">
                                <option value="">— None / Manual —</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}"
                                        {{ (int)($settings['admission_fee_service_id'] ?? 0) === $service->id ? 'selected' : '' }}>
                                        {{ $service->name }} — GH₵{{ number_format($service->price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">One-time fee at admission (type: Admission).</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Detention Fee Service</label>
                            <select name="detention_fee_service_id" class="form-select">
                                <option value="">— None / Manual —</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}"
                                        {{ (int)($settings['detention_fee_service_id'] ?? 0) === $service->id ? 'selected' : '' }}>
                                        {{ $service->name }} — GH₵{{ number_format($service->price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">One-time fee at admission (type: Detention).</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Consumable Fee Service <span class="badge bg-secondary fw-normal ms-1">Daily</span></label>
                            <select name="consumable_fee_service_id" class="form-select">
                                <option value="">— None / Manual —</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}"
                                        {{ (int)($settings['consumable_fee_service_id'] ?? 0) === $service->id ? 'selected' : '' }}>
                                        {{ $service->name }} — GH₵{{ number_format($service->price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Per-day consumables / detention charge applied during stay.</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
