{{--
    Phase 14R.6.1 — snapshot integrity state.

    Three closed states: verified / mismatch / unavailable.

    A mismatch is REPORTED, never repaired: the payload stays visible, the hash
    is not recalculated, no new snapshot is written and nothing is deleted.

    The wording is deliberately precise — the payload is verified against the
    hash stored at capture. That is tamper EVIDENCE, not an external signature,
    and it does not prove non-repudiation against a privileged database actor.

    Expects: $vm
--}}
@php($vm = $vm ?? null)

@if ($vm)
    @if ($vm->integrityVerified())
        <div class="small text-success mb-2">
            <i class="ti ti-shield-check me-1"></i>
            {{ __('consultation_maternity_summary.snapshot.verified') }}
            @if ($vm->shortHash())
                <span class="text-muted">({{ $vm->shortHash() }}…)</span>
            @endif
        </div>
    @elseif ($vm->integrityMismatch())
        <div class="alert alert-danger py-2 px-3 small mb-2">
            <div class="fw-semibold">
                <i class="ti ti-alert-triangle me-1"></i>
                {{ __('consultation_maternity_summary.snapshot.hash_mismatch') }}
            </div>
            <div>{{ __('consultation_maternity_summary.snapshot.tamper_evidence_warning') }}</div>
            <div class="text-muted mt-1">
                {{ __('consultation_maternity_summary.snapshot.cannot_be_edited') }}
                {{ __('consultation_maternity_summary.snapshot.cannot_be_deleted') }}
            </div>
        </div>
    @else
        <div class="small text-muted mb-2">
            <i class="ti ti-help-circle me-1"></i>
            {{ __('consultation_maternity_summary.snapshot.verification_unavailable') }}
        </div>
    @endif
@endif
