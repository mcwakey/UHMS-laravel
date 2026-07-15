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

    // Bucket each timeline event into a filterable category from its source_type.
    $previewCategoryOf = function (?string $sourceType): string {
        $s = strtolower((string) $sourceType);
        return match (true) {
            str_contains($s, 'invoice') || str_contains($s, 'payment') || str_contains($s, 'billing') || str_contains($s, 'claim') || str_contains($s, 'refund') => 'billing',
            str_contains($s, 'prescription') || str_contains($s, 'medication') || str_contains($s, 'mar') || str_contains($s, 'dispens') || str_contains($s, 'drug') || str_contains($s, 'administration') => 'medications',
            str_contains($s, 'investigation') || str_contains($s, 'lab') || str_contains($s, 'result') || str_contains($s, 'sample') || str_contains($s, 'radiology') => 'investigations',
            str_contains($s, 'procedure') || str_contains($s, 'theatre') || str_contains($s, 'service_render') => 'procedures',
            str_contains($s, 'complaint') || str_contains($s, 'hopc') || str_contains($s, 'exam') || str_contains($s, 'diagnos') || str_contains($s, 'treatment') || str_contains($s, 'consult') || str_contains($s, 'note') || str_contains($s, 'triage') || str_contains($s, 'vital') || str_contains($s, 'clinical') || str_contains($s, 'session') => 'clinical',
            default => 'other',
        };
    };

    $previewCategoryMeta = [
        'clinical'       => ['label' => __('visits.category_clinical'),       'icon' => 'ti-stethoscope'],
        'investigations' => ['label' => __('visits.category_investigations'), 'icon' => 'ti-test-pipe'],
        'procedures'     => ['label' => __('visits.category_procedures'),     'icon' => 'ti-activity'],
        'medications'    => ['label' => __('visits.category_medications'),    'icon' => 'ti-pill'],
        'billing'        => ['label' => __('visits.category_billing'),        'icon' => 'ti-receipt'],
        'other'          => ['label' => __('visits.category_other'),          'icon' => 'ti-dots-circle-horizontal'],
    ];

    $previewCategoryCounts = [];
    foreach ($timeline as $previewItem) {
        $c = $previewCategoryOf($previewItem['source_type'] ?? '');
        $previewCategoryCounts[$c] = ($previewCategoryCounts[$c] ?? 0) + 1;
    }
@endphp

{{-- ── Quick Summary Cards ──────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    {{-- Visit info --}}
    <!-- <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted small mb-1 text-uppercase fw-semibold">{{ __('visits.visit_header') }}</p>
                <h6 class="fw-bold mb-0">{{ $visit->visit_number }}</h6>
                <small class="text-muted">{{ $visit->visit_date?->format('d M Y') ?? '—' }}</small>
                <div class="mt-2">
                    @if($visit->visit_type)
                        <span class="badge bg-light text-dark border">{{ $visit->visit_type->translatedLabel() }}</span>
                    @endif
                    @if($visit->status)
                        <span class="badge bg-secondary ms-1">{{ $visit->status->translatedLabel() }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div> -->

    {{-- Patient info --}}
    <!-- <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted small mb-1 text-uppercase fw-semibold">{{ __('visits.patient_header') }}</p>
                <h6 class="fw-bold mb-0">{{ $visit->patient?->full_name ?? '—' }}</h6>
                <small class="text-muted">
                    {{ $visit->patient?->patient_number ?? '' }}
                    @if($visit->patient_age || $visit->patient?->age)
                        · {{ __('visits.age_label') }} {{ $visit->patient_age ?? $visit->patient?->age }}
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
    </div> -->

    {{-- Clinical summary --}}
    <!-- <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted small mb-1 text-uppercase fw-semibold">{{ __('visits.clinical_card') }}</p>
                <div class="small">
                    <div class="mb-1"><span class="text-muted">{{ __('visits.complaint_label') }}:</span> {{ Str::limit($summary['chief_complaint'] ?? '—', 50) }}</div>
                    <div class="mb-1"><span class="text-muted">{{ __('visits.dx_label') }}:</span> {{ Str::limit($summary['primary_diagnosis'] ?? '—', 50) }}</div>
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
                        <span class="badge bg-dark">{{ __('visits.admitted_badge') }}</span>
                    @endif
                </div>
                @can('mar_chart.view')
                <div class="mt-2">
                    <a href="{{ $workspaceRoutes->route('admin.visits.mar-chart', $visit) }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-layout-grid me-1"></i>{{ __('visits.medication_chart') }}
                    </a>
                </div>
                @endcan
            </div>
        </div>
    </div> -->

    {{-- Billing summary --}}
    <!-- <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted small mb-1 text-uppercase fw-semibold">{{ __('visits.billing_card') }}</p>
                <h6 class="fw-bold mb-0">{{ $summary['billing_status'] ?? '—' }}</h6>
                @if(($summary['total_billed'] ?? 0) > 0)
                    <small class="text-muted">{{ __('visits.billed_label') }}: ₵{{ number_format($summary['total_billed'], 2) }}</small>
                    @if(($summary['total_paid'] ?? 0) > 0)
                        <div><small class="text-success">{{ __('visits.paid_label') }}: ₵{{ number_format($summary['total_paid'], 2) }}</small></div>
                    @endif
                    @php $balance = ($summary['total_billed'] ?? 0) - ($summary['total_paid'] ?? 0); @endphp
                    @if($balance > 0)
                        <div><small class="text-danger">{{ __('visits.balance_label') }}: ₵{{ number_format($balance, 2) }}</small></div>
                    @endif
                @endif
                @if($visit->visitInsurance)
                    <div class="mt-1">
                        <small class="text-muted">
                            <i class="ti ti-shield-check me-1"></i>{{ $visit->visitInsurance->insuranceProvider?->name ?? __('visits.insurance') }}
                        </small>
                    </div>
                @endif
            </div>
        </div>
    </div> -->
</div>

{{-- ── Timeline ─────────────────────────────────────────────────── --}}
@if(!empty($summary['next_appointment']))
@php $nextAppointment = $summary['next_appointment']; @endphp
<div class="alert alert-info border-0 mb-4">
    <div class="d-flex align-items-start gap-2">
        <i class="ti ti-calendar-plus fs-4 mt-1"></i>
        <div>
            <h6 class="fw-semibold mb-1">{{ __('visits.next_appointment_heading') }}</h6>
            <div class="small">
                <span class="fw-medium">{{ $nextAppointment['date'] ?? __('visits.date_not_set') }}</span>
                @if(!empty($nextAppointment['time']))
                    <span class="text-muted ms-1">{{ $nextAppointment['time'] }}</span>
                @endif
                @if(!empty($nextAppointment['department']))
                    <span class="text-muted ms-1">- {{ $nextAppointment['department'] }}</span>
                @endif
            </div>
            <div class="small text-muted">
                @if(!empty($nextAppointment['doctor'])){{ $nextAppointment['doctor'] }}@endif
                @if(!empty($nextAppointment['service'])){{ !empty($nextAppointment['doctor']) ? ' - ' : '' }}{{ $nextAppointment['service'] }}@endif
                @if(!empty($nextAppointment['reason'])){{ (!empty($nextAppointment['doctor']) || !empty($nextAppointment['service'])) ? ' - ' : '' }}{{ $nextAppointment['reason'] }}@endif
            </div>
        </div>
    </div>
</div>
@endif

<div class="card mb-0">
    <div class="card-header bg-transparent border-bottom-0 pb-0">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="fw-semibold mb-0"><i class="ti ti-timeline me-2"></i>{{ __('visits.clinical_timeline') }}</h6>
            @if(!empty($timeline))
                <div class="d-flex flex-wrap gap-1 d-print-none" id="previewTimelineFilters" role="group" aria-label="{{ __('visits.clinical_timeline') }}">
                    <button type="button" class="btn btn-sm btn-primary" data-preview-filter="all">{{ __('visits.all_filter') }} <span class="badge bg-white text-dark ms-1">{{ count($timeline) }}</span></button>
                    @foreach($previewCategoryMeta as $key => $meta)
                        @if(($previewCategoryCounts[$key] ?? 0) > 0)
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-preview-filter="{{ $key }}">
                                <i class="ti {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
                                <span class="badge bg-light text-dark ms-1">{{ $previewCategoryCounts[$key] }}</span>
                            </button>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if(empty($timeline))
            <div class="text-center py-5 text-muted">
                <i class="ti ti-timeline-event fs-1 d-block mb-2"></i>
                {{ __('visits.no_timeline_yet') }}
            </div>
        @else
            @foreach($timeline as $item)
                @include('visits.partials.visit-preview-timeline', ['item' => $item, 'category' => $previewCategoryOf($item['source_type'] ?? '')])
            @endforeach
            <div class="text-center py-4 text-muted d-none" id="previewTimelineEmptyFilter">
                <i class="ti ti-filter-off fs-3 d-block mb-2"></i>{{ __('visits.no_events_category') }}
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    #previewTimelineFilters .btn { --bs-btn-padding-y: .2rem; --bs-btn-padding-x: .55rem; }
    @media print {
        #previewTimelineFilters { display: none !important; }
        .preview-timeline-item.d-none { display: flex !important; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var group = document.getElementById('previewTimelineFilters');
    if (!group) return;
    var rows = Array.prototype.slice.call(document.querySelectorAll('.preview-timeline-item'));
    var emptyMsg = document.getElementById('previewTimelineEmptyFilter');

    group.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-preview-filter]');
        if (!btn) return;
        var filter = btn.getAttribute('data-preview-filter');

        group.querySelectorAll('[data-preview-filter]').forEach(function (b) {
            var active = b === btn;
            b.classList.toggle('btn-primary', active);
            b.classList.toggle('btn-outline-secondary', !active);
        });

        var shown = 0;
        rows.forEach(function (row) {
            var show = filter === 'all' || row.getAttribute('data-preview-category') === filter;
            row.classList.toggle('d-none', !show);
            if (show) shown++;
        });
        if (emptyMsg) emptyMsg.classList.toggle('d-none', shown > 0);
    });
})();
</script>
@endpush
