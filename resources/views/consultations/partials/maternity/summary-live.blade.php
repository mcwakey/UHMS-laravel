{{--
    Phase 14R.6.1 — the LIVE maternity projection for an active (or reopened)
    consultation.

    Labelled "Current Maternity Record" and never as completion-time data.
    Renders from the prepared view model only; zero queries.

    Expects: $maternitySummary (ConsultationMaternitySummaryViewModel)
--}}
@php($vm = $maternitySummary ?? null)

@if ($vm && $vm->shouldRender() && $vm->isLive())
    <div class="card mt-3" id="maternity-summary-live">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="fw-bold mb-0">
                <i class="ti ti-heartbeat me-1"></i>{{ __('consultation_maternity_summary.summary.current_record') }}
            </h6>
            <span class="text-muted" style="font-size: .74rem;">
                {{ __('consultation_maternity_summary.summary.source_of_truth') }}
                &middot;
                {{ __('consultation_maternity_summary.summary.encounter_source') }}
            </span>
        </div>

        <div class="card-body">
            @foreach ($vm->warnings as $warning)
                <div class="alert alert-warning py-2 px-3 small mb-2">{{ $warning }}</div>
            @endforeach

            @if ($vm->isGynaecology && $vm->hasClinicalContent())
                <p class="text-muted small mb-2">
                    {{ __('consultation_maternity.gynaecology.remains_gynaecology') }}
                </p>
            @endif

            @include('consultations.partials.maternity.summary-payload', [
                'payload' => $vm->payload(),
                'compact' => $vm->printMode,
            ])

            @if ($vm->isReopened && $vm->hasHistory())
                {{-- Reopened: the live record is current, but earlier completion
                     snapshots still exist and remain reachable. --}}
                <div class="alert alert-info py-2 px-3 small mt-3 mb-0">
                    <div class="fw-semibold">{{ __('consultation_maternity_summary.reopened.title') }}</div>
                    <div>{{ __('consultation_maternity_summary.reopened.live_values_shown') }}</div>
                    <div>{{ __('consultation_maternity_summary.reopened.recompletion_creates_version') }}</div>
                </div>

                @include('consultations.partials.maternity.snapshot-history', ['vm' => $vm])
            @endif
        </div>
    </div>
@endif
