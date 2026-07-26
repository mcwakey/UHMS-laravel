{{--
    Phase 14R.5.1 — link an existing Postnatal Case for review.

    Read-only from Consultation: no case is created, no mother/newborn
    observation is created, and the consultation note stays encounter-owned.
    Candidates are same-patient (mother) records prepared by the presenter.

    Expects: $action
--}}
<div class="mb-3">
    <label class="form-label" for="{{ $action->modalId }}Case">
        {{ __('maternity_handoffs.postnatal.link_case') }}
        <span class="text-danger">*</span>
    </label>
    <select name="postnatal_case_id" id="{{ $action->modalId }}Case" required
            class="form-select @error('postnatal_case_id') is-invalid @enderror">
        <option value="">{{ __('maternity_handoffs.modal.select_context') }}</option>
        @foreach (($action->context['candidates'] ?? []) as $candidate)
            <option value="{{ $candidate['id'] }}"
                @selected((int) old('postnatal_case_id') === (int) $candidate['id'])>
                {{ $candidate['label'] }}
            </option>
        @endforeach
    </select>
    @error('postnatal_case_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>

<input type="hidden" name="link_role" value="reviewed">

<div class="alert alert-light border py-2 px-3 small mb-0">
    <div>{{ __('maternity_handoffs.postnatal.observations_remain_in_maternity') }}</div>
    <div class="text-muted">{{ __('maternity_handoffs.postnatal.no_observation_created') }}</div>
</div>
