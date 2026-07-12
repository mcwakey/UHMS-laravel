@extends('layouts.app')
@section('title', __('payment_timing_cutover.title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('payment_timing_cutover.title') }}</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('menu.dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment_timing_cutover.title') }}</li>
        </ol></nav>
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

@if($forceLegacy)
    <div class="alert alert-danger"><i class="ti ti-shield-lock me-1"></i><strong>{{ __('payment_timing_cutover.force_legacy_on') }}</strong> — {{ __('payment_timing_cutover.force_legacy_help') }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">{{ __('payment_timing_cutover.labels.configured_mode') }}</div>
        <div class="h5 mb-0">{{ $configuredMode->label() }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100 border-{{ $effectiveMode->value === 'active' ? 'success' : 'secondary' }}"><div class="card-body">
        <div class="text-muted small">{{ __('payment_timing_cutover.labels.effective_mode') }}</div>
        <div class="h5 mb-0">{{ $effectiveMode->label() }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">{{ __('payment_timing_cutover.labels.force_legacy') }}</div>
        <div class="h5 mb-0">{{ $forceLegacy ? __('payment_timing_cutover.on') : __('payment_timing_cutover.off') }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">{{ __('payment_timing_cutover.labels.failure_fallback') }}</div>
        <div class="h5 mb-0">{{ $failureFallback ? __('payment_timing_cutover.on') : __('payment_timing_cutover.off') }}</div>
    </div></div></div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><h6 class="fw-bold mb-0">{{ __('payment_timing_cutover.master_controls') }}</h6></div>
            <div class="card-body">
                <div class="alert alert-warning py-2">{{ __('payment_timing_cutover.master_help') }}</div>
                @can('billing.payment_timing.cutover.manage')
                <form method="POST" action="{{ route('admin.billing.payment-timing-cutover.master') }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" for="cutover_mode">{{ __('payment_timing_cutover.labels.configured_mode') }}</label>
                        <select name="mode" id="cutover_mode" class="form-select">
                            @foreach($masterModes as $m)
                                <option value="{{ $m->value }}" @selected($configuredMode === $m)>{{ $m->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="master_reason">{{ __('payment_timing_cutover.labels.reason') }}</label>
                        <textarea name="reason" id="master_reason" rows="2" class="form-control" maxlength="1000" required></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="confirm" value="1" id="master_confirm">
                        <label class="form-check-label" for="master_confirm">{{ __('payment_timing_cutover.labels.confirm_active') }}</label>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('payment_timing_cutover.actions.save_master') }}</button>
                </form>
                @else
                    <p class="text-muted mb-0">{{ __('payment_timing_cutover.view_only') }}</p>
                @endcan
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-3 border-danger">
            <div class="card-header"><h6 class="fw-bold mb-0 text-danger">{{ __('payment_timing_cutover.rollback_title') }}</h6></div>
            <div class="card-body">
                <p>{{ __('payment_timing_cutover.rollback_help') }}</p>
                @can('billing.payment_timing.cutover.rollback')
                <form method="POST" action="{{ route('admin.billing.payment-timing-cutover.rollback') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="rollback_reason">{{ __('payment_timing_cutover.labels.reason') }}</label>
                        <input type="text" name="reason" id="rollback_reason" class="form-control" maxlength="1000" required>
                    </div>
                    <button type="submit" class="btn btn-danger"><i class="ti ti-arrow-back-up me-1"></i>{{ __('payment_timing_cutover.actions.rollback') }}</button>
                </form>
                @else
                    <p class="text-muted mb-0">{{ __('payment_timing_cutover.view_only') }}</p>
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h6 class="fw-bold mb-0">{{ __('payment_timing_cutover.eligible_operations') }}</h6></div>
    <div class="card-body">
        @if(empty($operations))
            <p class="text-muted mb-0">{{ __('payment_timing_cutover.no_operations') }}</p>
        @else
            @foreach($operations as $op)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                        <div>
                            <code class="fw-semibold">{{ $op['operation'] }}</code>
                            <span class="badge bg-secondary ms-2">{{ $op['mode']->label() }}</span>
                            <span class="text-muted small ms-2">{{ __('payment_timing_cutover.labels.supported_visit_types') }}: {{ implode(', ', $op['supported_visit_types']) ?: '—' }}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            @if(! $op['emergency_supported'])<span class="badge bg-light text-dark border">{{ __('payment_timing_cutover.emergency_not_supported') }}</span>@endif
                            @foreach($op['blockers'] as $b)<span class="badge bg-warning text-dark">{{ __('payment_timing_cutover.blockers.'.$b) }}</span>@endforeach
                        </div>
                    </div>
                    @if($op['compatibility_description'])
                        <div class="alert alert-info py-2 small mb-2">{{ $op['compatibility_description'] }}</div>
                    @endif
                    @can('billing.payment_timing.cutover.manage')
                    <form method="POST" action="{{ route('admin.billing.payment-timing-cutover.operation') }}" class="row g-2 align-items-end">
                        @csrf @method('PUT')
                        <input type="hidden" name="operation" value="{{ $op['operation'] }}">
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('payment_gate.configured_mode') }}</label>
                            <select name="mode" class="form-select form-select-sm">
                                @foreach($operationModes as $m)
                                    <option value="{{ $m->value }}" @selected($op['mode'] === $m)>{{ $m->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($op['requires_acknowledgement'])
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="compatibility_acknowledged" value="1" id="ack_{{ $loop->index }}" @checked($op['acknowledged'])>
                                <label class="form-check-label small" for="ack_{{ $loop->index }}">{{ __('payment_timing_cutover.labels.acknowledge_compatibility') }}</label>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <input type="text" name="reason" class="form-control form-control-sm" maxlength="1000" placeholder="{{ __('payment_timing_cutover.labels.reason') }}" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">{{ __('payment_timing_cutover.actions.save_operation') }}</button>
                        </div>
                    </form>
                    @endcan
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
