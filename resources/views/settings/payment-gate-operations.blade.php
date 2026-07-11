@extends('layouts.app')
@section('title', __('payment_gate.title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('settings.title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('menu.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('payment_gate.title') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card"><div class="card-body p-0">@include('settings.partials.sidebar')</div></div>
    </div>
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('payment_gate.title') }}</h5>
                <p class="text-muted small mb-0 mt-1">{{ __('payment_gate.subtitle') }}</p>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <div class="alert alert-info d-flex align-items-start gap-2">
                    <i class="ti ti-info-circle mt-1"></i>
                    <div>
                        <div>{{ __('payment_gate.not_operational_notice') }}</div>
                        <div class="small text-muted mt-1">{{ __('payment_gate.read_only_notice') }}</div>
                    </div>
                </div>

                @if(empty($groups))
                    <p class="text-muted mb-0">{{ __('payment_gate.no_operations') }}</p>
                @else
                <form method="POST" action="{{ route('admin.settings.payment-gate-operations.update') }}">
                    @csrf
                    @method('PUT')

                    @foreach($groups as $family => $items)
                        <h6 class="fw-bold mt-4 mb-3">{{ __('payment_gate.family.'.$family) }}</h6>

                        @foreach($items as $item)
                            @php
                                $op = $item['operation'];
                                $def = $item['definition'];
                                $policy = $item['policy'];
                                $elig = $item['eligibility'];
                                $compat = $item['compatibility'];
                                $editable = $item['editable'];
                                $wired = (bool) ($def['production_wired'] ?? false);
                                $hardGate = (bool) ($def['hard_enforcement'] ?? false);
                            @endphp

                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                    <div>
                                        <code class="fw-semibold">{{ $op }}</code>
                                        <span class="text-muted small ms-2">{{ __('payment_gate.workflow_stage') }}: {{ $def['stage']->value ?? '—' }}</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1">
                                        @if($wired)
                                            <span class="badge bg-secondary">{{ __('payment_gate.currently_wired') }}</span>
                                        @else
                                            <span class="badge bg-light text-dark border">{{ __('payment_gate.currently_unwired') }}</span>
                                        @endif
                                        @if($hardGate)
                                            <span class="badge bg-warning text-dark">{{ __('payment_gate.existing_hard_gate') }}</span>
                                        @endif
                                        <span class="badge {{ $elig->eligible ? 'bg-success' : 'bg-light text-dark border' }}">
                                            {{ __('payment_gate.eligibility.'.$elig->status) }}
                                        </span>
                                        <span class="badge bg-light text-dark border">{{ __('payment_gate.compatibility.'.$compat['status']) }}</span>
                                    </div>
                                </div>

                                <div class="text-muted small mb-3">
                                    {{ __('payment_gate.legacy_behaviour') }}: {{ $def['legacy_behaviour'] ?? '—' }}
                                </div>

                                @if($editable)
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label small">{{ __('payment_gate.configured_mode') }}</label>
                                            <select name="operations[{{ $op }}][mode]" class="form-select form-select-sm">
                                                @foreach($modes as $mode)
                                                    <option value="{{ $mode->value }}" @selected(old("operations.$op.mode", $policy->mode->value) === $mode->value)>{{ $mode->label() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">{{ __('payment_gate.missing_billing_context') }}</label>
                                            <select name="operations[{{ $op }}][missing_context]" class="form-select form-select-sm">
                                                @foreach($missingContextOptions as $opt)
                                                    <option value="{{ $opt->value }}" @selected(old("operations.$op.missing_context", $policy->missingBillingContext->value) === $opt->value)>{{ $opt->label() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">{{ __('payment_gate.visit_context_rule') }}</label>
                                            <select name="operations[{{ $op }}][visit_context_rule]" class="form-select form-select-sm">
                                                @foreach($visitContextOptions as $opt)
                                                    <option value="{{ $opt->value }}" @selected(old("operations.$op.visit_context_rule", $policy->visitContextRule->value) === $opt->value)>{{ $opt->label() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">{{ __('payment_gate.override_scope_label') }}</label>
                                            <select name="operations[{{ $op }}][override_scope_rule]" class="form-select form-select-sm">
                                                @foreach($overrideScopeOptions as $opt)
                                                    <option value="{{ $opt->value }}" @selected(old("operations.$op.override_scope_rule", $policy->overrideScopeRule->value) === $opt->value)>{{ $opt->label() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-4 mt-3">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="operations[{{ $op }}][emergency_exempt]" value="0">
                                            <input class="form-check-input" type="checkbox" value="1" id="emergency_{{ $loop->parent->index }}_{{ $loop->index }}"
                                                   name="operations[{{ $op }}][emergency_exempt]" @checked(old("operations.$op.emergency_exempt", $policy->emergencyExempt))>
                                            <label class="form-check-label small" for="emergency_{{ $loop->parent->index }}_{{ $loop->index }}">{{ __('payment_gate.emergency_exempt') }}</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="operations[{{ $op }}][inpatient_exempt]" value="0">
                                            <input class="form-check-input" type="checkbox" value="1" id="inpatient_{{ $loop->parent->index }}_{{ $loop->index }}"
                                                   name="operations[{{ $op }}][inpatient_exempt]" @checked(old("operations.$op.inpatient_exempt", $policy->inpatientExempt))>
                                            <label class="form-check-label small" for="inpatient_{{ $loop->parent->index }}_{{ $loop->index }}">{{ __('payment_gate.inpatient_exempt') }}</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="operations[{{ $op }}][typed_enforcement_eligible]" value="0">
                                            <input class="form-check-input" type="checkbox" value="1" id="typed_{{ $loop->parent->index }}_{{ $loop->index }}"
                                                   name="operations[{{ $op }}][typed_enforcement_eligible]" @checked(old("operations.$op.typed_enforcement_eligible", $policy->enabledForFutureTypedEnforcement))>
                                            <label class="form-check-label small" for="typed_{{ $loop->parent->index }}_{{ $loop->index }}">{{ __('payment_gate.typed_enforcement_eligibility') }}</label>
                                        </div>
                                    </div>
                                @else
                                    <div class="row g-2 small text-muted">
                                        <div class="col-md-3">{{ __('payment_gate.configured_mode') }}: <span class="text-body">{{ $policy->mode->label() }}</span></div>
                                        <div class="col-md-3">{{ __('payment_gate.missing_billing_context') }}: <span class="text-body">{{ $policy->missingBillingContext->label() }}</span></div>
                                        <div class="col-md-3">{{ __('payment_gate.visit_context_rule') }}: <span class="text-body">{{ $policy->visitContextRule->label() }}</span></div>
                                        <div class="col-md-3">{{ __('payment_gate.override_scope_label') }}: <span class="text-body">{{ $policy->overrideScopeRule->label() }}</span></div>
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge bg-light text-dark border"><i class="ti ti-lock me-1"></i>{{ __('payment_gate.read_only_notice') }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endforeach

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('settings.save_changes') }}</button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
