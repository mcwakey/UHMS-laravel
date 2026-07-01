@extends('layouts.app')
@section('title', __('triage.queue'))

@section('content')
<x-page-header :title="__('triage.queue')" :description="__('triage.patients_awaiting_today')" icon="ti-heart-broken"/>

<!-- Page Header -->
<!-- <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-heart-broken me-2 text-info"></i>{{ __('triage.queue') }}</h4>
        <small class="text-muted">{{ __('triage.patients_awaiting_today') }}</small>
    </div>
    <a href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>{{ __('triage.all_visits') }}
    </a>
</div> -->

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti ti-circle-check me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $invoiceSettlement = app(\App\Services\Billing\InvoiceItemSettlementService::class);
@endphp

<div class="row g-3">
    {{-- ── Awaiting Triage (WAITING) ───────────────────────────────────────── --}}
    @php $waiting = $visits->where('status', \App\Enums\VisitStatus::QUEUED)->values(); @endphp
    <div class="col-6">
        <div class="card border-warning border-opacity-50">
            <div class="card-header d-flex align-items-center gap-2 bg-warning bg-opacity-10">
                <i class="ti ti-clock-hour4 text-warning fs-5"></i>
                <h6 class="fw-bold mb-0 text-warning">{{ __('triage.awaiting_triage') }}</h6>
                <span class="ms-auto badge bg-warning text-dark rounded-pill">{{ $waiting->count() }}</span>
            </div>
            <div class="card-body p-0">
                @forelse($waiting as $visit)
                    @php
                        $queueEntry = $visit->queueEntries->first();
                        $patientBillBalance = (float) $visit->invoices
                            ->flatMap->items
                            ->sum(fn ($item) => $invoiceSettlement->outstandingBalance($item));
                        $hasUnpaidBill = $patientBillBalance > 0;
                    @endphp
                    <div class="d-flex align-items-center px-3 py-2 border-bottom hover-bg-light">
                        <div class="flex-shrink-0 text-center me-3">
                            @if($queueEntry)
                                <span class="badge bg-warning text-dark fs-6">#{{ $queueEntry->queue_number }}</span>
                                <div class="text-muted" style="font-size:0.7rem;">{{ __('triage.queue_label') }}</div>
                            @else
                                <span class="badge bg-light text-muted">-</span>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $visit->patient->full_name }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                {{ $visit->patient->patient_number }}
                                &bull; {{ $visit->visit_number }}
                                &bull; <x-status-badge :status="$visit->priority" class="py-0" />
                                &bull; <x-status-badge :status="$visit->status" class="py-0" />
                            </div>
                            @if($visit->chief_complaint)
                                <div class="text-muted" style="font-size:0.78rem;">{{ Str::limit($visit->chief_complaint, 60) }}</div>
                            @endif
                        </div>
                        @if($hasUnpaidBill)
                            <button type="button" class="btn btn-outline-muted btn-sm ms-2" disabled title="{{ __('triage.pay_bill_before_triage', ['amount' => '₵'.number_format($patientBillBalance, 2)]) }}">
                                <i class="ti ti-lock me-1"></i>{{ __('triage.start_triage') }}
                            </button>
                        @else
                            <a href="{{ route('admin.triage.create', $visit) }}" class="btn btn-warning btn-sm ms-2">
                                <i class="ti ti-stethoscope me-1"></i>{{ __('triage.start_triage') }}
                            </a>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-2 small">
                        <i class="ti ti-circle-check fs-3 d-block mb-1 text-success"></i>
                        {{ __('triage.no_patients_awaiting') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── On Assessment (TRIAGE) ──────────────────────────────────────────── --}}
    @php $onAssessment = $visits->where('status', \App\Enums\VisitStatus::TRIAGE)->values(); @endphp
    <div class="col-6">
        <div class="card border-info border-opacity-50">
            <div class="card-header d-flex align-items-center gap-2 bg-info bg-opacity-10">
                <i class="ti ti-activity text-info fs-5"></i>
                <h6 class="fw-bold mb-0 text-info">{{ __('triage.on_assessment') }}</h6>
                <span class="ms-auto badge bg-info rounded-pill">{{ $onAssessment->count() }}</span>
            </div>
            <div class="card-body p-0">
                @forelse($onAssessment as $visit)
                    @php
                        $queueEntry = $visit->queueEntries->first();
                        $patientBillBalance = (float) $visit->invoices
                            ->flatMap->items
                            ->sum(fn ($item) => $invoiceSettlement->outstandingBalance($item));
                        $hasUnpaidBill = $patientBillBalance > 0;
                    @endphp
                    <div class="d-flex align-items-center px-3 py-2 border-bottom hover-bg-light">
                        <div class="flex-shrink-0 text-center me-3">
                            @if($queueEntry)
                                <span class="badge bg-info fs-6">#{{ $queueEntry->queue_number }}</span>
                                <div class="text-muted" style="font-size:0.7rem;">{{ __('triage.queue_label') }}</div>
                            @else
                                <span class="badge bg-light text-muted">-</span>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $visit->patient->full_name }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                {{ $visit->patient->patient_number }}
                                &bull; {{ $visit->visit_number }}
                                &bull; <x-status-badge :status="$visit->priority" class="py-0" />
                                &bull; <x-status-badge :status="$visit->status" class="py-0" />
                            </div>
                            @if($visit->chief_complaint)
                                <div class="text-muted" style="font-size:0.78rem;">{{ Str::limit($visit->chief_complaint, 60) }}</div>
                            @endif
                        </div>
                        @if($hasUnpaidBill)
                            <button type="button" class="btn btn-outline-muted btn-sm ms-2" disabled title="{{ __('triage.pay_bill_before_triage', ['amount' => '₵'.number_format($patientBillBalance, 2)]) }}">
                                <i class="ti ti-lock me-1"></i>{{ __('triage.continue_triage') }}
                            </button>
                        @else
                        <a href="{{ route('admin.triage.create', $visit) }}" class="btn btn-info btn-sm ms-2">
                            <i class="ti ti-arrow-right me-1"></i>{{ __('triage.continue_triage') }}
                        </a>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-2 small">
                        <i class="ti ti-circle-check fs-3 d-block mb-1 text-success"></i>
                        {{ __('triage.no_patients_on_assessment') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
