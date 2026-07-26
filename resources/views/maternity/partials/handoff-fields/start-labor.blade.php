{{--
    Phase 14R.5.1 — start or open a Labor Episode from Emergency.

    Only the inputs the existing LaborEpisodeService handoff route accepts are
    offered. The full Labor form is deliberately NOT duplicated inside
    Emergency — Maternity owns that record.

    Expects: $action
--}}
<input type="hidden" name="pregnancy_profile_id" value="{{ $action->context['pregnancy_profile_id'] ?? '' }}">

@unless ($action->reusesExistingRecord())
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="{{ $action->modalId }}Onset">
                {{ __('maternity_handoffs.modal.labor_onset_at') }}
            </label>
            <input type="datetime-local" name="labor_onset_at" id="{{ $action->modalId }}Onset"
                   class="form-control @error('labor_onset_at') is-invalid @enderror"
                   value="{{ old('labor_onset_at') }}">
            @error('labor_onset_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="{{ $action->modalId }}Presentation">
                {{ __('maternity_handoffs.modal.presentation') }}
            </label>
            <input type="text" name="presentation" id="{{ $action->modalId }}Presentation"
                   class="form-control @error('presentation') is-invalid @enderror"
                   maxlength="60" value="{{ old('presentation') }}">
            @error('presentation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>
@endunless

<div class="alert alert-light border py-2 px-3 small mt-3 mb-0">
    <div>{{ __('maternity_handoffs.emergency.emergency_owns_acute_care') }}</div>
    <div>{{ __('maternity_handoffs.emergency.maternity_owns_labor') }}</div>
</div>
