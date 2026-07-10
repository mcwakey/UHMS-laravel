@props([
    'visit' => null,        // App\Models\Visit — care context for the override gate
    'patient' => null,      // App\Models\Patient — falls back to the visit/invoice patient
    'invoice' => null,      // App\Models\Invoice — convenience (derives patient + visit)
    'compact' => false,     // slim single-line variant for tight layouts
])

@php
    use App\Services\Billing\PatientOutstandingBalanceService;
    use App\Services\Billing\PreviousBalanceOverrideService;

    $visit = $visit ?: $invoice?->visit;
    $patient = $patient ?: ($visit?->patient ?: $invoice?->patient);

    $render = false;
    $summary = null;
    $eval = null;
    $user = auth()->user();
    $canAmount = (bool) $user?->can('billing.previous_balance.amount.view');
    $canFlag = $canAmount || (bool) $user?->can('billing.previous_balance.flag.view');
    $canOverride = (bool) $user?->can('billing.previous_balance.override');

    if ($patient && config('billing.previous_balance_policy.enabled', true) && $canFlag) {
        $summary = app(PatientOutstandingBalanceService::class)->buildPatientBalanceSummary($patient, $visit);
        if (! empty($summary['has_previous_outstanding'])) {
            $render = true;
            if ($visit) {
                $eval = app(PreviousBalanceOverrideService::class)->evaluate($visit);
            }
        }
    }

    $isEmergency = $eval && ($eval['reason'] ?? null) === 'EMERGENCY_NEVER_BLOCKED';
    $isBlocked = $eval['blocked'] ?? false;
    $hasActiveOverride = $eval['has_active_override'] ?? false;

    // danger when OPD service is blocked, info for emergency (never blocked), warning otherwise
    $variant = $isBlocked ? 'danger' : ($isEmergency ? 'info' : 'warning');
    $icon = $isBlocked ? 'ti-lock-dollar' : ($isEmergency ? 'ti-ambulance' : 'ti-alert-triangle');

    $money = fn ($v) => '₵' . number_format((float) $v, 2);
@endphp

@if($render)
<div class="alert alert-{{ $variant }} border-0 shadow-sm {{ $compact ? 'py-2 px-3 mb-2' : 'mb-3' }}" role="alert">
    <div class="d-flex align-items-start gap-2">
        <i class="ti {{ $icon }} fs-4 flex-shrink-0"></i>
        <div class="flex-grow-1">
            @if($canAmount)
                <div class="fw-semibold mb-1">
                    {{ __('billing.previous_balance_warning', ['amount' => $money($summary['previous_outstanding'])]) }}
                </div>

                @unless($compact)
                <div class="row g-2 mt-1 mb-1">
                    <div class="col-auto">
                        <div class="small text-muted">{{ __('billing.previous_visits_outstanding') }}</div>
                        <div class="fw-bold">{{ $money($summary['previous_outstanding']) }}</div>
                    </div>
                    @if($visit)
                    <div class="col-auto">
                        <div class="small text-muted">{{ __('billing.current_visit_outstanding') }}</div>
                        <div class="fw-bold">{{ $money($summary['current_visit_outstanding']) }}</div>
                    </div>
                    @endif
                    <div class="col-auto">
                        <div class="small text-muted">{{ __('billing.total_patient_outstanding') }}</div>
                        <div class="fw-bold">{{ $money($summary['total_outstanding']) }}</div>
                    </div>
                    @if($summary['oldest_age_days'] !== null)
                    <div class="col-auto">
                        <div class="small text-muted">{{ __('billing.oldest_balance_age') }}</div>
                        <div class="fw-bold">
                            {{ __('billing.age_days', ['days' => $summary['oldest_age_days']]) }}
                            <span class="badge bg-{{ $variant }}-subtle text-{{ $variant }} ms-1">{{ $summary['ar_bucket'] }}</span>
                        </div>
                    </div>
                    @endif
                </div>
                @endunless
            @else
                {{-- Flag-only users (e.g. clinical roles): no amounts, generic message. --}}
                <div class="fw-semibold">
                    @if($isBlocked)
                        {{ __('billing.billing_clearance_required') }}
                    @else
                        {{ __('billing.outstanding_balance_exists') }}
                    @endif
                </div>
            @endif

            @if($isEmergency)
                <div class="small mt-1">{{ __('billing.emergency_care_not_blocked_by_debt') }}</div>
            @elseif($isBlocked)
                <div class="small mt-1">{{ __('billing.previous_balance_override_required') }}</div>
            @elseif($hasActiveOverride)
                <div class="small mt-1"><i class="ti ti-shield-check me-1"></i>{{ __('billing.previous_balance_override_active') }}</div>
            @endif

            @if($canAmount)
            <div class="d-flex flex-wrap gap-2 mt-2">
                @can('billing.patient_statement.view')
                <a href="{{ route('admin.billing.statements.show', $patient) }}" class="btn btn-sm btn-outline-{{ $variant }}">
                    <i class="ti ti-file-text me-1"></i>{{ __('billing.view_patient_statement') }}
                </a>
                @endcan
                @can('payments.create')
                <a href="{{ route('admin.billing.payments.receive', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-outline-{{ $variant }}">
                    <i class="ti ti-cash me-1"></i>{{ __('billing.collect_old_balance') }}
                </a>
                @endcan
                @if($isBlocked && $canOverride && $visit)
                <a href="#" class="btn btn-sm btn-{{ $variant }}"
                   data-bs-toggle="modal" data-bs-target="#previousBalanceOverrideModal">
                    <i class="ti ti-key me-1"></i>{{ __('billing.request_override') }}
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endif
