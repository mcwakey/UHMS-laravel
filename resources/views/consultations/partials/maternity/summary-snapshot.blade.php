{{--
    Phase 14R.6.1 — the COMPLETION SNAPSHOT view for a completed consultation.

    This renders the STORED payload. No live maternity service is called for the
    values shown here, so a later correction to the Pregnancy Profile or ANC
    record cannot alter what this consultation reported at completion.

    Also handles the honest no-snapshot case: it never fabricates history.

    Expects: $maternitySummary (ConsultationMaternitySummaryViewModel)
--}}
@php($vm = $maternitySummary ?? null)

@if ($vm && $vm->shouldRender() && ($vm->isSnapshot() || $vm->isMissingSnapshot()))
    <div class="card mt-3 border-secondary-subtle" id="maternity-summary-snapshot">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 bg-light">
            <h6 class="fw-bold mb-0">
                <i class="ti ti-history me-1"></i>{{ __('consultation_maternity_summary.summary.completion_snapshot_title') }}
            </h6>

            @if ($vm->isSnapshot())
                <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                    {{ __('consultation_maternity_summary.summary.completion_snapshot') }} v{{ $vm->snapshotVersion }}
                </span>
            @endif
        </div>

        <div class="card-body">
            @if ($vm->isMissingSnapshot())
                {{-- Completed, but nothing was captured. Say so plainly. --}}
                <div class="alert alert-secondary py-2 px-3 small mb-0">
                    <div class="fw-semibold">{{ __('consultation_maternity_summary.summary.no_snapshot_available') }}</div>
                    <div>{{ __('consultation_maternity_summary.snapshot.completed_before_capture_enabled') }}</div>
                    <div>{{ __('consultation_maternity_summary.snapshot.none_fabricated') }}</div>
                    <div class="text-muted mt-1">
                        {{ __('consultation_maternity_summary.current.may_differ') }}
                    </div>
                </div>
            @else
                <div class="d-flex flex-wrap gap-3 small text-muted mb-2">
                    <span>{{ __('consultation_maternity_summary.summary.captured_at') }}: <strong>{{ $vm->capturedAt }}</strong></span>
                    @if ($vm->capturedBy)
                        <span>{{ __('consultation_maternity_summary.summary.captured_by') }}: <strong>{{ $vm->capturedBy }}</strong></span>
                    @endif
                    <span>{{ __('consultation_maternity_summary.summary.schema_version') }}: <strong>{{ $vm->schemaVersion }}</strong></span>
                    @if ($vm->pregnancyProfileId)
                        <span>{{ __('maternity_handoffs.cards.pregnancy') }}: <strong>#{{ $vm->pregnancyProfileId }}</strong></span>
                    @endif
                </div>

                @include('consultations.partials.maternity.snapshot-integrity', ['vm' => $vm])

                <div class="alert alert-secondary py-2 px-3 small">
                    {{ __('consultation_maternity_summary.summary.historical_label') }}
                </div>

                @include('consultations.partials.maternity.summary-payload', [
                    'payload' => $vm->payload(),
                    'compact' => $vm->printMode,
                ])
            @endif

            @unless ($vm->printMode)
                @include('consultations.partials.maternity.snapshot-history', ['vm' => $vm])
                @include('consultations.partials.maternity.current-record', ['vm' => $vm])
            @endunless
        </div>
    </div>
@endif
