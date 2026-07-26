{{--
    Phase 14R.6.1 — the lazily-loaded CURRENT maternity record fragment.

    Rendered only after an explicit clinician action, and always labelled as
    current data that is NOT part of the completion-time snapshot. It never
    compares against, modifies or replaces the historical snapshot.

    Expects: $payload (array), $available (bool)
--}}
<div class="border rounded p-2 bg-body-tertiary">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <div class="fw-semibold small">{{ __('consultation_maternity_summary.summary.current_record') }}</div>
        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
            {{ __('consultation_maternity_summary.current.not_part_of_snapshot') }}
        </span>
    </div>

    @if (! $available)
        <p class="text-muted small mb-0">
            {{ __('consultation_maternity_summary.current.unavailable') }}
        </p>
    @else
        @include('consultations.partials.maternity.summary-payload', [
            'payload' => $payload,
            'compact' => true,
        ])

        <div class="text-muted mt-2" style="font-size: .74rem;">
            {{ __('consultation_maternity_summary.current.loaded_separately') }}
        </div>
    @endif
</div>
