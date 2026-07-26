{{--
    Phase 14R.5.1 — record an ANC visit from the Obstetrics workspace.

    Delegates to AntenatalVisitService; only the fields the existing route
    accepts are offered. The full ANC form lives in Maternity.

    Expects: $action
--}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="{{ $action->modalId }}Date">
            {{ __('maternity_handoffs.modal.visit_date') }}
        </label>
        <input type="date" name="visit_date" id="{{ $action->modalId }}Date"
               class="form-control @error('visit_date') is-invalid @enderror"
               value="{{ old('visit_date', now()->toDateString()) }}">
        @error('visit_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $action->modalId }}Next">
            {{ __('maternity_handoffs.fields.next_visit') }}
        </label>
        <input type="date" name="next_visit_date" id="{{ $action->modalId }}Next"
               class="form-control @error('next_visit_date') is-invalid @enderror"
               value="{{ old('next_visit_date') }}">
        @error('next_visit_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>
