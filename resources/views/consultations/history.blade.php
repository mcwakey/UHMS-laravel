@extends('layouts.app')
@section('title', 'Consultation Summary - ' . $visit->visit_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Consultation Summary</h4>
        <small class="text-muted">Visit {{ $visit->visit_number }} &middot; {{ $visit->patient->full_name }} ({{ $visit->patient->patient_number }})</small>
    </div>
    <div>
        <a href="{{ route('admin.consultations.show', $visit) }}" class="btn btn-outline-primary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Consultation
        </a>
    </div>
</div>

@include('partials.patient-visit-header', ['visit' => $visit, 'showAlerts' => true])

@php $lv = $visit->vitals->first(); @endphp

{{-- ── VITALS ─────────────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="fw-bold mb-0 small"><i class="ti ti-heartbeat me-1 text-danger"></i>Latest Vitals</h6>
    </div>
    <div class="card-body py-2">
        @if($lv)
        <div class="row g-2 text-center">
            <div class="col-6 col-sm-4 col-md-2">
                <div class="text-muted" style="font-size:.68rem">Blood Pressure</div>
                <div class="fw-bold">{{ $lv->blood_pressure ?? '—' }}</div>
                <div class="text-muted" style="font-size:.68rem">mmHg</div>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <div class="text-muted" style="font-size:.68rem">Heart Rate</div>
                <div class="fw-bold">{{ $lv->heart_rate ?? '—' }}</div>
                <div class="text-muted" style="font-size:.68rem">bpm</div>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <div class="text-muted" style="font-size:.68rem">Temperature</div>
                <div class="fw-bold">{{ $lv->temperature ?? '—' }}</div>
                <div class="text-muted" style="font-size:.68rem">°C</div>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <div class="text-muted" style="font-size:.68rem">SpO₂</div>
                <div class="fw-bold">{{ $lv->spo2 ?? '—' }}</div>
                <div class="text-muted" style="font-size:.68rem">%</div>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <div class="text-muted" style="font-size:.68rem">Resp. Rate</div>
                <div class="fw-bold">{{ $lv->respiratory_rate ?? '—' }}</div>
                <div class="text-muted" style="font-size:.68rem">/min</div>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <div class="text-muted" style="font-size:.68rem">BMI</div>
                <div class="fw-bold">{{ $lv->bmi ?? '—' }}</div>
                <div class="text-muted" style="font-size:.68rem">kg/m²</div>
            </div>
        </div>
        <div class="text-muted mt-2" style="font-size:.7rem">
            <i class="ti ti-clock me-1"></i>{{ $lv->recorded_at->format('d M Y H:i') }} by {{ $lv->recordedBy?->full_name ?? 'Unknown' }}
            @if($visit->vitals->count() > 1)
                &middot; {{ $visit->vitals->count() - 1 }} earlier reading(s)
            @endif
        </div>
        @else
            <p class="text-muted small mb-0">No vitals recorded for this visit.</p>
        @endif
    </div>
</div>

@if($sessions->count() > 1)
{{-- ═══════════════════════════════════════════════════════════════════════════
     MULTI-SESSION: one accordion panel per consultation route
═══════════════════════════════════════════════════════════════════════════ --}}
<div class="accordion mb-3" id="sessionsAccordion">
    @foreach($sessions as $i => $session)
    @php
        $statusColor = match($session->status) {
            'ACTIVE'    => 'success',
            'COMPLETED' => 'secondary',
            'PAUSED'    => 'warning',
            'CANCELLED' => 'danger',
            default     => 'info',
        };
    @endphp
    <div class="accordion-item border shadow-sm mb-2 rounded">
        <h2 class="accordion-header" id="sh-{{ $session->id }}">
            <button class="accordion-button rounded {{ $i > 0 ? 'collapsed' : '' }} py-2"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#sc-{{ $session->id }}"
                    aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                    aria-controls="sc-{{ $session->id }}">
                <span class="badge bg-{{ $statusColor }} me-2">{{ $session->status }}</span>
                <strong>{{ $session->department?->name ?? 'General Consultation' }}</strong>
                @if($session->doctor)
                    <span class="text-muted ms-2 small fw-normal">— Dr. {{ $session->doctor->full_name }}</span>
                @endif
                @if($session->started_at)
                    <span class="text-muted ms-auto me-3 small fw-normal d-none d-sm-inline">{{ $session->started_at->format('d M Y, h:i A') }}</span>
                @endif
            </button>
        </h2>
        <div id="sc-{{ $session->id }}"
             class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
             aria-labelledby="sh-{{ $session->id }}"
             data-bs-parent="#sessionsAccordion">
            <div class="accordion-body p-2 pt-3">
                @include('partials.consultation-clinical-sections', ['r' => $session->medicalRecord])
            </div>
        </div>
    </div>
    @endforeach
</div>

@else
{{-- ═══════════════════════════════════════════════════════════════════════════
     SINGLE SESSION or LEGACY: flat layout
═══════════════════════════════════════════════════════════════════════════ --}}
@if(!$record)
<div class="card mb-3">
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-file-off fs-1 d-block mb-2"></i>
        <p>No consultation record has been started for this visit yet.</p>
    </div>
</div>
@else
@include('partials.consultation-clinical-sections', ['r' => $record])
@endif

@endif {{-- end multi vs single --}}

{{-- ══════════════════════════════════════════════════════════════════════════
     VISIT-WIDE: Investigations and Procedure Requests (always shown)
══════════════════════════════════════════════════════════════════════════ --}}

{{-- ── INVESTIGATIONS (Lab Requests) ──────────────────────────────────────── --}}
@php $labGrouped = $labRequests->groupBy(fn($r) => $r->targetDepartment?->name ?? 'Other'); @endphp
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="fw-bold mb-0 small"><i class="ti ti-test-pipe me-1 text-purple"></i>Investigations <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $labRequests->sum(fn($r) => $r->items?->count() ?? 0) }}</span></h6>
    </div>
    <div class="card-body py-2">
        @if($labGrouped->isNotEmpty())
        @foreach($labGrouped as $deptName => $reqs)
        <div class="mb-3">
            <h6 class="small fw-bold text-muted text-uppercase border-bottom pb-1 mb-2">
                <i class="ti ti-building-hospital me-1"></i>{{ $deptName }}
            </h6>
            @foreach($reqs as $req)
                @foreach($req->items ?? [] as $item)
                <div class="border-start border-3 border-secondary ps-3 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-medium small">{{ $item->display_name ?? $item->name }}</span>
                            <span class="badge bg-{{ $item->status_color }} ms-1">{{ ucfirst($item->status) }}</span>
                            @if($item->result?->is_verified)
                                <span class="badge bg-success ms-1"><i class="ti ti-check"></i> Verified</span>
                            @elseif($item->result)
                                <span class="badge bg-warning ms-1">Result Pending Verification</span>
                            @endif
                            <div><small class="text-muted">Req #{{ $req->request_number }} &middot; {{ $req->created_at?->format('d M Y H:i') }}</small></div>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            @if($item->result)
                            <a href="{{ route('admin.lab.results.view', $item) }}" class="btn btn-xs btn-outline-info" title="View Result"><i class="ti ti-eye"></i></a>
                            @endif
                            @if($item->result?->is_verified)
                            <a data-no-inertia href="{{ route('admin.lab.results.print', $item) }}" target="_blank" class="btn btn-xs btn-outline-secondary" title="Print"><i class="ti ti-printer"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            @endforeach
        </div>
        @endforeach
        @else
        <p class="text-muted small mb-0"><i class="ti ti-minus me-1"></i>None requested.</p>
        @endif
    </div>
</div>

{{-- ── PROCEDURE REQUESTS ──────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="fw-bold mb-0 small"><i class="ti ti-activity-heartbeat me-1 text-warning"></i>Procedure Requests <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $procedureRequests->count() }}</span></h6>
    </div>
    <div class="card-body py-2">
        @forelse($procedureRequests as $pr)
        <div class="border-start border-3 border-warning ps-3 mb-2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="badge" style="background-color:{{ $pr->status->color() }};color:#fff">{{ $pr->status->label() }}</span>
                    <span class="fw-medium ms-1">{{ $pr->service?->name ?? 'Procedure' }}</span>
                    <small class="text-muted">&middot; {{ $pr->request_number }}</small>
                    <div><small class="text-muted">
                        {{ ucfirst($pr->priority) }} &middot; {{ $pr->department?->name }}
                        @if($pr->schedule) &middot; Scheduled {{ optional($pr->schedule->scheduled_start)->format('d M Y H:i') }} @endif
                    </small></div>
                    @if($pr->indication) <div><small><strong>Indication:</strong> {{ $pr->indication }}</small></div> @endif
                </div>
                <a href="{{ route('admin.theatre.show', $pr) }}" class="btn btn-xs btn-outline-primary flex-shrink-0"><i class="ti ti-eye me-1"></i>Open</a>
            </div>
        </div>
        @empty
        <p class="text-muted small mb-0"><i class="ti ti-minus me-1"></i>None requested.</p>
        @endforelse
    </div>
</div>

@endsection

