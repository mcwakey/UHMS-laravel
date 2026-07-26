{{--
    Phase 14R.5.1 — explicit Pregnancy Profile creation from Emergency.

    Reached ONLY by pressing this button. Never triggered by a complaint, a
    diagnosis, a danger sign, patient sex or a pregnancy test. Creation runs
    through PregnancyProfileService, so Maternity keeps ownership.

    Expects: $action
--}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="{{ $action->modalId }}Lmp">
            {{ __('maternity_handoffs.modal.last_menstrual_period') }}
        </label>
        <input type="date" name="last_menstrual_period" id="{{ $action->modalId }}Lmp"
               class="form-control @error('last_menstrual_period') is-invalid @enderror"
               value="{{ old('last_menstrual_period') }}">
        @error('last_menstrual_period')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $action->modalId }}Edd">
            {{ __('maternity_handoffs.fields.edd') }}
        </label>
        <input type="date" name="estimated_due_date" id="{{ $action->modalId }}Edd"
               class="form-control @error('estimated_due_date') is-invalid @enderror"
               value="{{ old('estimated_due_date') }}">
        @error('estimated_due_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $action->modalId }}Gravida">
            {{ __('maternity_handoffs.modal.gravida') }}
        </label>
        <input type="number" min="0" max="30" name="gravida" id="{{ $action->modalId }}Gravida"
               class="form-control @error('gravida') is-invalid @enderror" value="{{ old('gravida') }}">
        @error('gravida')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $action->modalId }}Para">
            {{ __('maternity_handoffs.modal.para') }}
        </label>
        <input type="number" min="0" max="30" name="para" id="{{ $action->modalId }}Para"
               class="form-control @error('para') is-invalid @enderror" value="{{ old('para') }}">
        @error('para')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>
