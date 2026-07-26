{{--
    Phase 14R.5.1 — Admission Request handover fields.

    Shared by the Consultation and Emergency dialogs; the operational source
    (consultation vs emergency) is decided server-side and only DISPLAYED here.

    Only safe handover fields are collected. The full consultation note is never
    copied — the summary is capped and the server truncates to 500 characters.

    Expects: $action
--}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="{{ $action->modalId }}Priority">
            {{ __('maternity_handoffs.modal.priority') }}
        </label>
        <select name="priority" id="{{ $action->modalId }}Priority"
                class="form-select @error('priority') is-invalid @enderror">
            @foreach (['routine', 'urgent', 'emergency'] as $priority)
                <option value="{{ $priority }}"
                    @selected(old('priority', $action->context['default_priority'] ?? 'routine') === $priority)>
                    {{ __('maternity_handoffs.priorities.'.$priority) }}
                </option>
            @endforeach
        </select>
        @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $action->modalId }}Ward">
            {{ __('maternity_handoffs.modal.requested_ward') }}
        </label>
        {{-- Bounded, active-only ward list prepared by the workspace service.
             Beds are NOT loaded: this action never reserves one. --}}
        <select name="requested_ward_id" id="{{ $action->modalId }}Ward"
                class="form-select @error('requested_ward_id') is-invalid @enderror">
            <option value="">{{ __('maternity_handoffs.modal.no_ward_preference') }}</option>
            @foreach (($action->context['wards'] ?? []) as $ward)
                <option value="{{ $ward['id'] }}" @selected((int) old('requested_ward_id') === (int) $ward['id'])>
                    {{ $ward['name'] }}
                </option>
            @endforeach
        </select>
        @error('requested_ward_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label" for="{{ $action->modalId }}Diagnosis">
            {{ __('maternity_handoffs.modal.provisional_diagnosis') }}
        </label>
        <input type="text" name="provisional_diagnosis" id="{{ $action->modalId }}Diagnosis"
               class="form-control @error('provisional_diagnosis') is-invalid @enderror"
               maxlength="1000"
               value="{{ old('provisional_diagnosis', $action->context['default_diagnosis'] ?? '') }}">
        @error('provisional_diagnosis')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label" for="{{ $action->modalId }}Summary">
            {{ __('maternity_handoffs.modal.clinical_handover_summary') }}
        </label>
        <textarea name="clinical_summary" id="{{ $action->modalId }}Summary" rows="3"
                  class="form-control @error('clinical_summary') is-invalid @enderror"
                  maxlength="500">{{ old('clinical_summary') }}</textarea>
        @error('clinical_summary')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="form-text">{{ __('maternity_handoffs.modal.summary_not_full_note') }}</div>
    </div>
</div>

<div class="alert alert-light border py-2 px-3 small mt-3 mb-0">
    <div><strong>{{ __('maternity_handoffs.modal.operational_source') }}:</strong>
        {{ $action->context['operational_source'] ?? '—' }}</div>
    <div><strong>{{ __('maternity_handoffs.modal.clinical_context') }}:</strong>
        {{ $action->context['clinical_context'] ?? '—' }}</div>
    <div class="text-muted mt-1">
        {{ __('maternity_handoffs.modal.no_admission_created') }}
        {{ __('maternity_handoffs.modal.no_bed_reserved') }}
    </div>
</div>
