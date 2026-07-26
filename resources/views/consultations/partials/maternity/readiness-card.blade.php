{{--
    Phase 14R.6.1 — ADVISORY maternity readiness card.

    Renders nothing unless CONSULTATION_MATERNITY_READINESS_ENABLED is on, the
    specialty is Obstetrics, and the clinician holds the readiness permission —
    all decided by ConsultationMaternityReadinessService, not here.

    It is advisory in the strongest sense: it states that completion is still
    allowed, offers no mutation, and copies no admission discharge rule.

    Expects: $readiness (ConsultationMaternityReadinessResult|null)
--}}
@php($readiness = $readiness ?? null)

@if ($readiness && $readiness->shouldRender())
    <div class="card mb-3 border-info-subtle" id="maternity-readiness">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="fw-bold mb-0">
                <i class="ti ti-clipboard-heart me-1"></i>{{ __('consultation_maternity_summary.readiness.title') }}
            </h6>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-secondary border fw-normal">{{ $readiness->modeLabel() }}</span>
                @if ($readiness->isReady())
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        {{ __('consultation_maternity_summary.readiness.statuses.ready') }}
                    </span>
                @else
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                        {{ __('consultation_maternity_summary.readiness.statuses.warning') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="card-body py-2">
            @if ($readiness->hasWarnings())
                <ul class="mb-2 ps-3 small">
                    @foreach ($readiness->warnings() as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
                <div class="text-muted small">
                    {{ __('consultation_maternity_summary.readiness.record_in_maternity') }}
                </div>
            @endif

            {{-- The load-bearing statement: this never blocks completion. --}}
            <div class="text-muted fst-italic mt-1" style="font-size: .75rem;">
                {{ __('consultation_maternity_summary.readiness.advisory_notice') }}
            </div>
        </div>
    </div>
@endif
