@extends('layouts.app')
@section('title', 'Visit ' . $visit->visit_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Visit {{ $visit->visit_number }}</h4>
        <small class="text-muted">Created {{ $visit->created_at->format('d M Y, h:i A') }} by {{ $visit->createdBy?->full_name }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Visits
        </a>
        @can('visits.edit')
        <a href="{{ route('admin.visits.edit', $visit) }}" class="btn btn-outline-warning btn-md">
            <i class="ti ti-pencil me-1"></i>Edit Visit
        </a>
        @endcan
        @can('visits.create')
        <a href="{{ route('admin.visits.create', ['patient_id' => $visit->patient_id]) }}" class="btn btn-outline-primary btn-md">
            <i class="ti ti-plus me-1"></i>New Visit for Patient
        </a>
        @endcan
    </div>
</div>

<div class="row">
    <!-- Left Column — Visit Info -->
    <div class="col-lg-8">
        <!-- Status Bar -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0">Visit Status Flow</h6>
                    <span class="badge bg-{{ $visit->status->color() }} fs-14 px-3 py-2">{{ $visit->status->label() }}</span>
                </div>
                <!-- Status Timeline -->
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    @php
                        $statusFlow = [
                            \App\Enums\VisitStatus::REGISTERED,
                            \App\Enums\VisitStatus::WAITING,
                            \App\Enums\VisitStatus::TRIAGE,
                            \App\Enums\VisitStatus::CONSULTING,
                            \App\Enums\VisitStatus::LAB,
                            \App\Enums\VisitStatus::PHARMACY,
                            \App\Enums\VisitStatus::BILLING,
                            \App\Enums\VisitStatus::COMPLETED,
                        ];
                        $visitedStatuses = $visit->statusLogs->pluck('to_status')->toArray();
                        $currentStatus = $visit->status;
                    @endphp
                    @foreach($statusFlow as $i => $flowStatus)
                        @php
                            $isVisited = in_array($flowStatus->value, $visitedStatuses);
                            $isCurrent = $currentStatus === $flowStatus;
                            $stepClass = $isCurrent ? 'bg-' . $flowStatus->color() . ' text-white' : ($isVisited ? 'bg-success-subtle text-success' : 'bg-light text-muted');
                        @endphp
                        <span class="badge rounded-pill {{ $stepClass }} px-2 py-1 small">
                            @if($isVisited && !$isCurrent)<i class="ti ti-check me-1"></i>@endif
                            {{ $flowStatus->label() }}
                        </span>
                        @if(!$loop->last)
                            <i class="ti ti-chevron-right text-muted small"></i>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Status Transition Actions -->
        @php
            $isWaiting  = $visit->status === \App\Enums\VisitStatus::WAITING;
            $isTriage   = $visit->status === \App\Enums\VisitStatus::TRIAGE;
            $serviceDepts = $visit->visitServices->pluck('department')->filter()->unique('id');

            // Statuses that can use the department send button (triage or at a service dept)
            $canSendToDept = in_array($visit->status, [
                \App\Enums\VisitStatus::TRIAGE,
                \App\Enums\VisitStatus::CONSULTING,
                \App\Enums\VisitStatus::LAB,
                \App\Enums\VisitStatus::PHARMACY,
                \App\Enums\VisitStatus::BILLING,
            ]);
        @endphp

        @if($isWaiting || $isTriage || $visit->status->allowedTransitions())
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-switch-horizontal me-1"></i>Transition Visit</h6>
            </div>
            <div class="card-body">

                {{-- WAITING: Triage / Cancelled / Reschedule only --}}
                @if($isWaiting)
                <p class="text-muted small mb-2">Select the next step for this patient:</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach([\App\Enums\VisitStatus::TRIAGE, \App\Enums\VisitStatus::CANCELLED, \App\Enums\VisitStatus::RESCHEDULED] as $nextStatus)
                        <form method="POST" action="{{ route('admin.visits.transition', $visit) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            <button type="submit" class="btn btn-{{ $nextStatus->color() }} btn-sm"
                                    onclick="return confirm('Move visit to {{ $nextStatus->label() }}?')">
                                <i class="ti ti-arrow-right me-1"></i>{{ $nextStatus->label() }}
                            </button>
                        </form>
                    @endforeach
                </div>

                {{-- TRIAGE: primary action is triage assessment form --}}
                @elseif($isTriage)
                <div class="mb-3">
                    <a href="{{ route('admin.triage.create', $visit) }}" class="btn btn-info">
                        <i class="ti ti-stethoscope me-1"></i>Start Triage Assessment
                    </a>
                    @if($visit->triage)
                        <a href="{{ route('admin.triage.show', $visit) }}" class="btn btn-outline-info btn-sm ms-2">
                            <i class="ti ti-eye me-1"></i>View Triage Record
                        </a>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.visits.transition', $visit) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ \App\Enums\VisitStatus::CANCELLED->value }}">
                        <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return confirm('Cancel this visit?')">
                            <i class="ti ti-x me-1"></i>Cancel Visit
                        </button>
                    </form>
                </div>

                {{-- All other statuses: standard transition buttons --}}
                @elseif($visit->status->allowedTransitions())
                @if($canSendToDept && $serviceDepts->isNotEmpty())
                    <p class="text-muted small mb-2">Send to another department:</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach($serviceDepts as $dept)
                            <form method="POST" action="{{ route('admin.visits.send-to-department', $visit) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                <button type="submit"
                                        class="btn btn-outline-{{ $dept->type?->color() ?? 'primary' }} btn-sm"
                                        onclick="return confirm('Send patient to {{ $dept->name }}?')">
                                    <i class="ti ti-building-hospital me-1"></i>{{ $dept->name }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                @endif
                <div class="d-flex flex-wrap gap-2">
                    @foreach($visit->status->allowedTransitions() as $nextStatus)
                        <form method="POST" action="{{ route('admin.visits.transition', $visit) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            <button type="submit" class="btn btn-{{ $nextStatus->color() }} btn-sm"
                                    onclick="return confirm('Move visit to {{ $nextStatus->label() }}?')">
                                <i class="ti ti-arrow-right me-1"></i>{{ $nextStatus->label() }}
                            </button>
                        </form>
                    @endforeach
                </div>
                @endif

            </div>
        </div>
        @endif

        {{-- Triage Summary Card (shown once triage exists) --}}
        @if($visit->triage)
        @php $triage = $visit->triage; @endphp
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1 text-info"></i>Triage Assessment</h6>
                @if($triage->triage_score)
                    <span class="badge bg-{{ $triage->triage_score->color() }}">{{ $triage->triage_score->label() }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @if($triage->blood_pressure)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">BP</div>
                            <div class="fw-semibold small">{{ $triage->blood_pressure }} mmHg</div>
                        </div>
                    @endif
                    @if($triage->heart_rate)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">Heart Rate</div>
                            <div class="fw-semibold small">{{ $triage->heart_rate }} bpm</div>
                        </div>
                    @endif
                    @if($triage->temperature)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">Temp</div>
                            <div class="fw-semibold small">{{ $triage->temperature }} °C</div>
                        </div>
                    @endif
                    @if($triage->spo2)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">SpO₂</div>
                            <div class="fw-semibold small">{{ $triage->spo2 }}%</div>
                        </div>
                    @endif
                    @if($triage->respiratory_rate)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">Resp. Rate</div>
                            <div class="fw-semibold small">{{ $triage->respiratory_rate }}/min</div>
                        </div>
                    @endif
                    @if($triage->bmi)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">BMI</div>
                            <div class="fw-semibold small">{{ $triage->bmi }} kg/m²</div>
                        </div>
                    @endif
                </div>
                @if($triage->department)
                    <div class="mt-2 small text-muted">
                        <i class="ti ti-building-hospital me-1"></i>Assigned to: <strong>{{ $triage->department->name }}</strong>
                    </div>
                @endif
                <div class="mt-2 d-flex gap-2">
                    <a href="{{ route('admin.triage.show', $visit) }}" class="btn btn-outline-info btn-sm">
                        <i class="ti ti-eye me-1"></i>Full Triage Report
                    </a>
                    @if($visit->status === \App\Enums\VisitStatus::TRIAGE)
                        <a href="{{ route('admin.triage.create', $visit) }}" class="btn btn-info btn-sm">
                            <i class="ti ti-pencil me-1"></i>Re-assess
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Department History Card (shown after triage assigns dept) --}}
        @if($visit->departmentHistory->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-list-details me-1"></i>Department Journey</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($visit->departmentHistory as $hist)
                                <tr>
                                    <td class="small fw-semibold">{{ $hist->department?->name ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $hist->typeColor() }}">{{ $hist->typeLabel() }}</span></td>
                                    <td><span class="badge bg-{{ $hist->statusColor() }}">{{ $hist->statusLabel() }}</span></td>
                                    <td class="text-muted small">{{ $hist->created_at->format('h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Triage Summary Card (shown once triage exists) --}}
        @if($visit->triage)
        @php $triage = $visit->triage; @endphp
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1 text-info"></i>Triage Assessment</h6>
                @if($triage->triage_score)
                    <span class="badge bg-{{ $triage->triage_score->color() }}">{{ $triage->triage_score->label() }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @if($triage->blood_pressure)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">BP</div>
                            <div class="fw-semibold small">{{ $triage->blood_pressure }} mmHg</div>
                        </div>
                    @endif
                    @if($triage->heart_rate)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">Heart Rate</div>
                            <div class="fw-semibold small">{{ $triage->heart_rate }} bpm</div>
                        </div>
                    @endif
                    @if($triage->temperature)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">Temp</div>
                            <div class="fw-semibold small">{{ $triage->temperature }} °C</div>
                        </div>
                    @endif
                    @if($triage->spo2)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">SpO₂</div>
                            <div class="fw-semibold small">{{ $triage->spo2 }}%</div>
                        </div>
                    @endif
                    @if($triage->respiratory_rate)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">Resp. Rate</div>
                            <div class="fw-semibold small">{{ $triage->respiratory_rate }}/min</div>
                        </div>
                    @endif
                    @if($triage->bmi)
                        <div class="col-6 col-md-3 text-center">
                            <div class="text-muted" style="font-size:0.72rem">BMI</div>
                            <div class="fw-semibold small">{{ $triage->bmi }} kg/m²</div>
                        </div>
                    @endif
                </div>
                @if($triage->department)
                    <div class="mt-2 small text-muted">
                        <i class="ti ti-building-hospital me-1"></i>Assigned to: <strong>{{ $triage->department->name }}</strong>
                    </div>
                @endif
                <div class="mt-2 d-flex gap-2">
                    <a href="{{ route('admin.triage.show', $visit) }}" class="btn btn-outline-info btn-sm">
                        <i class="ti ti-eye me-1"></i>Full Triage Report
                    </a>
                    @if($visit->status === \App\Enums\VisitStatus::TRIAGE)
                        <a href="{{ route('admin.triage.create', $visit) }}" class="btn btn-info btn-sm">
                            <i class="ti ti-pencil me-1"></i>Re-assess
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Department History Card (shown after triage assigns dept) --}}
        @if($visit->departmentHistory->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-list-details me-1"></i>Department Journey</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($visit->departmentHistory as $hist)
                                <tr>
                                    <td class="small fw-semibold">{{ $hist->department?->name ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $hist->typeColor() }}">{{ $hist->typeLabel() }}</span></td>
                                    <td><span class="badge bg-{{ $hist->statusColor() }}">{{ $hist->statusLabel() }}</span></td>
                                    <td class="text-muted small">{{ $hist->created_at->format('h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Visit Details Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-clipboard-text me-1"></i>Visit Details</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small mb-1">Visit Type</label>
                        <div>
                            <span class="badge bg-{{ $visit->visit_type === \App\Enums\VisitType::EMERGENCY ? 'danger' : ($visit->visit_type === \App\Enums\VisitType::INPATIENT ? 'info' : 'light text-dark') }}">
                                {{ $visit->visit_type->label() }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small mb-1">Priority</label>
                        <div><span class="badge bg-{{ $visit->priority->color() }}">{{ $visit->priority->label() }}</span></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small mb-1">Visit Date</label>
                        <div class="fw-medium">{{ $visit->visit_date->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small mb-1">Assigned Doctor</label>
                        <div class="fw-medium">{{ $visit->assignedDoctor ? 'Dr. ' . $visit->assignedDoctor->full_name : '—' }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small mb-1">Duration</label>
                        <div class="fw-medium">{{ $visit->duration ?? '—' }}</div>
                    </div>
                </div>
                @if($visit->chief_complaint)
                <div class="mb-3">
                    <label class="text-muted small mb-1">Chief Complaint</label>
                    <div class="bg-light rounded p-3">{{ $visit->chief_complaint }}</div>
                </div>
                @endif
                @if($visit->notes)
                <div>
                    <label class="text-muted small mb-1">Notes</label>
                    <div class="bg-light rounded p-3">{{ $visit->notes }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Insurance Information -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-shield-check me-1"></i>Insurance</h6>
            </div>
            <div class="card-body">
            @if($insuranceInfo)
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="text-muted small mb-1">Provider</label>
                        <div class="fw-medium">{{ $insuranceInfo['provider']?->name ?? 'Cash & Carry' }}</div>
                        @if($insuranceInfo['provider'])
                        <div><span class="badge bg-{{ $insuranceInfo['provider']->type->color() }} mt-1">{{ $insuranceInfo['provider']->type->label() }}</span></div>
                        @endif
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted small mb-1">Tier &amp; Member Type</label>
                        <div class="fw-medium">{{ $insuranceInfo['tier_name'] ?? '—' }}</div>
                        @php
                            $memberTypeVal = $insuranceInfo['member_type'] ?? 'holder';
                            $memberTypeBadge = $memberTypeVal === 'beneficiary' ? 'warning' : 'info';
                            $memberTypeLabel = $memberTypeVal === 'beneficiary' ? 'Beneficiary' : 'Card Holder';
                        @endphp
                        <span class="badge bg-{{ $memberTypeBadge }} mt-1">{{ $memberTypeLabel }}</span>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted small mb-1">Coverage</label>
                        <div class="fw-medium">{{ $insuranceInfo['coverage_percentage'] ?? 0 }}%</div>
                        @if($insuranceInfo['per_visit_limit'])
                            <small class="text-muted">Per visit cap: &#8373;{{ number_format($insuranceInfo['per_visit_limit'], 2) }}</small>
                        @endif
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted small mb-1">Annual Remaining</label>
                        <div class="fw-bold {{ ($insuranceInfo['remaining_annual'] ?? 0) > 0 ? 'text-success' : 'text-danger' }}">
                            @if($insuranceInfo['annual_limit'])
                                &#8373;{{ number_format($insuranceInfo['remaining_annual'] ?? 0, 2) }}
                            @else
                                Unlimited
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted small mb-1">Monthly Remaining</label>
                        <div class="fw-medium">
                            @if($insuranceInfo['max_per_month'])
                                <span class="{{ ($insuranceInfo['remaining_monthly'] ?? 0) > 0 ? 'text-success' : 'text-danger' }}">
                                    &#8373;{{ number_format($insuranceInfo['remaining_monthly'] ?? 0, 2) }}
                                </span>
                            @else
                                <span class="text-muted">Unlimited</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted small mb-1">Membership #</label>
                        <div class="fw-medium">{{ $insuranceInfo['insurance']?->membership_number ?? '—' }}</div>
                    </div>
                </div>
                @if($insuranceInfo['annual_limit'])
                <div class="mt-2">
                    @php
                        $usagePercent = $insuranceInfo['annual_limit'] > 0 ? min(100, round(($insuranceInfo['used_this_year'] / $insuranceInfo['annual_limit']) * 100)) : 0;
                    @endphp
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Annual Usage: &#8373;{{ number_format($insuranceInfo['used_this_year'] ?? 0, 2) }} of &#8373;{{ number_format($insuranceInfo['annual_limit'], 2) }}</span>
                        <span>{{ $usagePercent }}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar {{ $usagePercent > 80 ? 'bg-danger' : ($usagePercent > 50 ? 'bg-warning' : 'bg-success') }}" style="width: {{ $usagePercent }}%"></div>
                    </div>
                </div>
                @endif
            @else
                <div class="text-center py-3">
                    <span class="badge bg-danger fs-14 px-3 py-2 mb-2">Cash &amp; Carry (Self-Sponsored)</span>
                    <p class="text-muted mb-0 small">No insurance selected for this visit. Patient pays full amount.</p>
                </div>
            @endif
            </div>
        </div>

        <!-- Visit Services -->
        @if($visit->visitServices->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-receipt me-1"></i>Visit Services</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Service</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Insurance</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalAmt = 0; $totalIns = 0; @endphp
                            @foreach($visit->visitServices as $vs)
                            <tr>
                                <td>
                                    {{ $vs->serviceCatalog?->name ?? '—' }}
                                    @if($vs->department)
                                        <span class="badge bg-light text-dark ms-1">{{ $vs->department->name }}</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $vs->quantity }}</td>
                                <td class="text-end">&#8373;{{ number_format($vs->unit_price, 2) }}</td>
                                <td class="text-end text-success">&#8373;{{ number_format($vs->insurance_covered ?? 0, 2) }}</td>
                                <td class="text-end fw-medium">&#8373;{{ number_format($vs->total_price, 2) }}</td>
                            </tr>
                            @php $totalAmt += $vs->total_price; $totalIns += ($vs->insurance_covered ?? 0); @endphp
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="3" class="text-end">Subtotal:</td>
                                <td class="text-end text-success">&#8373;{{ number_format($totalIns, 2) }}</td>
                                <td class="text-end">&#8373;{{ number_format($totalAmt, 2) }}</td>
                            </tr>
                            <tr class="table-warning fw-bold">
                                <td colspan="4" class="text-end">Patient Pays:</td>
                                <td class="text-end">&#8373;{{ number_format($totalAmt - $totalIns, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Status Timeline -->
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-timeline me-1"></i>Status History</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @foreach($visit->statusLogs as $log)
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-sm rounded-circle bg-{{ \App\Enums\VisitStatus::from($log->to_status)->color() }} text-white d-flex align-items-center justify-content-center">
                                <i class="ti ti-arrow-right fs-12"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    @if($log->from_status)
                                        <span class="badge bg-light text-dark">{{ \App\Enums\VisitStatus::from($log->from_status)->label() }}</span>
                                        <i class="ti ti-arrow-right text-muted mx-1"></i>
                                    @endif
                                    <span class="badge bg-{{ \App\Enums\VisitStatus::from($log->to_status)->color() }}">{{ \App\Enums\VisitStatus::from($log->to_status)->label() }}</span>
                                </div>
                                <small class="text-muted">{{ $log->timestamp->format('h:i A') }}</small>
                            </div>
                            <small class="text-muted">by {{ $log->changedBy?->full_name ?? 'System' }}</small>
                            @if($log->notes)
                                <div class="text-muted small mt-1">{{ $log->notes }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column — Patient Card -->
    <div class="col-lg-4">
        <!-- Patient Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-user me-1"></i>Patient</h6>
            </div>
            <div class="card-body text-center">
                @if($visit->patient->avatar)
                    <img src="{{ asset('storage/' . $visit->patient->avatar) }}" class="avatar avatar-xl rounded-circle mb-3" alt="">
                @else
                    <div class="avatar avatar-xl bg-primary rounded-circle text-white mx-auto mb-3 d-flex align-items-center justify-content-center">
                        <span class="fs-24">{{ strtoupper(substr($visit->patient->first_name, 0, 1) . substr($visit->patient->last_name, 0, 1)) }}</span>
                    </div>
                @endif
                <h5 class="fw-bold mb-1">{{ $visit->patient->full_name }}</h5>
                <p class="text-muted mb-2">{{ $visit->patient->patient_number }}</p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    @if($visit->patient->blood_group)
                        <span class="badge bg-danger">{{ $visit->patient->blood_group->label() }}</span>
                    @endif
                </div>

                <div class="text-start">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Age</span>
                        <span class="fw-medium">{{ $visit->patient_age ?? $visit->patient->age }} years</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Gender</span>
                        <span class="fw-medium">{{ $visit->patient->gender?->label() ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Phone</span>
                        <span class="fw-medium">{{ $visit->patient->phone }}</span>
                    </div>
                    @if($visit->patient->allergies)
                    <div class="mt-3">
                        <span class="text-muted small">Allergies</span>
                        <div class="alert alert-warning py-1 px-2 mt-1 mb-0 small">{{ $visit->patient->allergies }}</div>
                    </div>
                    @endif
                    @if($visit->patient->chronic_conditions)
                    <div class="mt-2">
                        <span class="text-muted small">Chronic Conditions</span>
                        <div class="alert alert-info py-1 px-2 mt-1 mb-0 small">{{ $visit->patient->chronic_conditions }}</div>
                    </div>
                    @endif
                </div>

                <a href="{{ route('admin.patients.show', $visit->patient) }}" class="btn btn-outline-primary btn-sm mt-3 w-100">
                    <i class="ti ti-external-link me-1"></i>View Full Profile
                </a>
            </div>
        </div>

        <!-- Queue Info -->
        @if($visit->queueEntries->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-list-numbers me-1"></i>Queue History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($visit->queueEntries as $qe)
                            <tr>
                                <td class="fw-bold">{{ $qe->queue_number }}</td>
                                <td>
                                    @if($qe->department)
                                        <span class="badge bg-light text-dark">{{ $qe->department->name }}</span>
                                    @else
                                        <span class="badge bg-info text-white">Triage</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $qe->status_badge }}">{{ $qe->status_label }}</span></td>
                                <td class="small text-muted">{{ $qe->created_at->format('h:i A') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($visit->visitServices->isNotEmpty())
                <div class="border-top px-3 py-2">
                    <p class="text-muted small fw-bold mb-1">Services</p>
                    <div class="d-flex flex-column gap-1">
                        @foreach($visit->visitServices as $vs)
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="small fw-medium">{{ $vs->serviceCatalog?->name ?? '—' }}</span>
                                @if($vs->department)
                                    <span class="badge bg-light text-dark ms-1 small">{{ $vs->department->name }}</span>
                                @endif
                            </div>
                            <div class="text-end small text-muted">
                                x{{ $vs->quantity }} &bull; &#8373;{{ number_format($vs->total_price, 2) }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @php $visitTotal = $visit->visitServices->sum('total_price'); $visitIns = $visit->visitServices->sum('insurance_covered'); @endphp
                    <div class="d-flex justify-content-between border-top mt-2 pt-1 small fw-bold">
                        <span>Patient Pays</span>
                        <span>&#8373;{{ number_format($visitTotal - $visitIns, 2) }}</span>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Services Being Done (hidden during triage — full detail shown in left column) -->
        @if($visit->visitServices->isNotEmpty() && !$isTriage)
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-list-check me-1"></i>Services</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($visit->visitServices as $vs)
                    <div class="list-group-item py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-medium">{{ $vs->serviceCatalog?->name ?? '—' }}</span>
                                @if($vs->department)
                                    <span class="badge bg-light text-dark ms-1 small">{{ $vs->department->name }}</span>
                                @endif
                            </div>
                            <span class="text-muted small">x{{ $vs->quantity }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Timestamps -->
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-clock me-1"></i>Timestamps</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Registered</span>
                    <span class="small">{{ $visit->created_at->format('d M Y, h:i A') }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Checked In</span>
                    <span class="small">{{ $visit->checked_in_at?->format('h:i A') ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Checked Out</span>
                    <span class="small">{{ $visit->checked_out_at?->format('h:i A') ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
