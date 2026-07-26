{{--
    Phase 14R.5.1 — lazy, patient-scoped Pregnancy Profile selector.

    Candidates are NOT rendered into the page and are NOT queried on page load:
    the select2 control fetches them from a server endpoint only when the
    clinician opens it. The patient scope comes from the ROUTE MODEL on the
    server, so no client input can widen it, and another patient's profile can
    never appear.

    Never preselects among multiple profiles — an ambiguous context must be
    resolved by an explicit human choice.

    Expects: $action
--}}
@php($selectId = $action->modalId.'Profile')
@php($searchUrl = $action->context['candidate_url'] ?? null)

<div class="mb-3">
    <label class="form-label" for="{{ $selectId }}">
        {{ __('maternity_handoffs.modal.select_pregnancy_profile') }}
        <span class="text-danger">*</span>
    </label>

    <select name="pregnancy_profile_id" id="{{ $selectId }}"
            class="form-select maternity-profile-select @error('pregnancy_profile_id') is-invalid @enderror"
            data-search-url="{{ $searchUrl }}"
            data-placeholder="{{ __('maternity_handoffs.modal.search_pregnancy_profile') }}"
            data-modal="#{{ $action->modalId }}"
            required>
        {{-- Deliberately empty: no preselection, no eager candidate load. --}}
        <option value=""></option>
        @if (old('pregnancy_profile_id'))
            <option value="{{ old('pregnancy_profile_id') }}" selected>
                {{ __('maternity_handoffs.cards.record_ref', [
                    'type' => __('maternity_handoffs.cards.pregnancy'),
                    'id' => old('pregnancy_profile_id'),
                ]) }}
            </option>
        @endif
    </select>

    @error('pregnancy_profile_id')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    <div class="form-text">{{ __('maternity_handoffs.modal.patient_scoped_search') }}</div>
</div>
