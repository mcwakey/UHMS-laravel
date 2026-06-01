{{--
    Visit Preview — Chronological clinical summary card.
    Used inside the full preview page. Can also be @include'd anywhere.

    Required variables:
        $visit   — App\Models\Visit (with relationships loaded)
        $preview — array returned by VisitPreviewService::build()
                   (keys: visit, timeline, summary)

    The timeline array contains items built by VisitPreviewService.
--}}

@php
    $timeline = $preview['timeline'] ?? [];
    $summary  = $preview['summary'] ?? [];
@endphp

{{-- ── Quick Summary Cards ──────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    {{-- Visit info --}}
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted small mb-1 text-uppercase fw-semibold">Visit</p>
                <h6 class="fw-bold mb-0">{{ $visit->visit_number }}</h6>
                <small class="text-muted">{{ $visit->visit_date?->format('d M Y') ?? '—' }}</small>
                <div class="mt-2">
                    @if($visit->visit_type)
                        <span class="badge bg-light text-dark border">{{ $visit->visit_type->label() }}</span>
                    @endif
                    @if($visit->status)
                        <span class="badge bg-secondary ms-1">{{ $visit->status->label() }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Patient info --}}
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted small mb-1 text-uppercase fw-semibold">Patient</p>
                <h6 class="fw-bold mb-0">{{ $visit->patient?->full_name ?? '—' }}</h6>
                <small class="text-muted">
                    {{ $visit->patient?->patient_number ?? '' }}
                    @if($visit->patient_age || $visit->patient?->age)
                        · Age {{ $visit->patient_age ?? $visit->patient?->age }}
                    @endif
                    @if($visit->patient?->gender)
                        · {{ ucfirst($visit->patient->gender instanceof \UnitEnum ? $visit->patient->gender->value : (string) $visit->patient->gender) }}
                    @endif
                </small>
                @if($visit->patient?->phone)
                    <div class="mt-1"><small class="text-muted"><i class="ti ti-phone me-1"></i>{{ $visit->patient->phone }}</small></div>
                @endif
            </div>
        </div>
    </div>

    {{-- Clinical summary --}}
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted small mb-1 text-uppercase fw-semibold">Clinical</p>
                <div class="small">
                    <div class="mb-1"><span class="text-muted">Complaint:</span> {{ Str::limit($summary['chief_complaint'] ?? '—', 50) }}</div>
                    <div class="mb-1"><span class="text-muted">Dx:</span> {{ Str::limit($summary['primary_diagnosis'] ?? '—', 50) }}</div>
                </div>
                <div class="mt-2 d-flex flex-wrap gap-1">
                    @if(($summary['prescriptions_count'] ?? 0) > 0)
                        <span class="badge bg-primary">{{ $summary['prescriptions_count'] }} Rx</span>
                    @endif
                    @if(($summary['investigations_count'] ?? 0) > 0)
                        <span class="badge bg-info">{{ $summary['investigations_count'] }} Lab</span>
                    @endif
                    @if(($summary['procedures_count'] ?? 0) > 0)
                        <span class="badge bg-warning text-dark">{{ $summary['procedures_count'] }} Proc</span>
                    @endif
                    @if(($summary['service_renderings_count'] ?? 0) > 0)
                        <span class="badge bg-success">{{ $summary['service_renderings_count'] }} Services</span>
                    @endif
                    @if(($summary['pending_service_renderings_count'] ?? 0) > 0)
                        <span class="badge bg-warning text-dark">{{ $summary['pending_service_renderings_count'] }} Pending Service</span>
                    @endif
                    @if($summary['has_admission'] ?? false)
                        <span class="badge bg-dark">Admitted</span>
                    @endif
                </div>
                @can('mar_chart.view')
                <div class="mt-2">
                    <a href="{{ route('admin.visits.mar-chart', $visit) }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-layout-grid me-1"></i>Medication Chart
                    </a>
                </div>
                @endcan
            </div>
        </div>
    </div>

    {{-- Billing summary --}}
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted small mb-1 text-uppercase fw-semibold">Billing</p>
                <h6 class="fw-bold mb-0">{{ $summary['billing_status'] ?? '—' }}</h6>
                @if(($summary['total_billed'] ?? 0) > 0)
                    <small class="text-muted">Billed: ₵{{ number_format($summary['total_billed'], 2) }}</small>
                    @if(($summary['total_paid'] ?? 0) > 0)
                        <div><small class="text-success">Paid: ₵{{ number_format($summary['total_paid'], 2) }}</small></div>
                    @endif
                    @php $balance = ($summary['total_billed'] ?? 0) - ($summary['total_paid'] ?? 0); @endphp
                    @if($balance > 0)
                        <div><small class="text-danger">Balance: ₵{{ number_format($balance, 2) }}</small></div>
                    @endif
                @endif
                @if($visit->visitInsurance)
                    <div class="mt-1">
                        <small class="text-muted">
                            <i class="ti ti-shield-check me-1"></i>{{ $visit->visitInsurance->insuranceProvider?->name ?? 'Insurance' }}
                        </small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Timeline ─────────────────────────────────────────────────── --}}
<div class="card mb-0">
    <div class="card-header bg-transparent border-bottom-0 pb-0">
        <h6 class="fw-semibold mb-0"><i class="ti ti-timeline me-2"></i>Clinical Timeline</h6>
    </div>
    <div class="card-body">
        @if(empty($timeline))
            <div class="text-center py-5 text-muted">
                <i class="ti ti-timeline-event fs-1 d-block mb-2"></i>
                No clinical activities recorded for this visit yet.
            </div>
        @else
            @foreach($timeline as $item)
                @include('visits.partials.visit-preview-timeline', ['item' => $item])
            @endforeach
        @endif
    </div>
</div>
