{{--
    Phase 14R.5.1 — explicit Maternity → Emergency escalation.

    The escalation FLAG on the source record created nothing. Only this
    confirmation creates or opens an Emergency Case, and it does so through the
    existing EmergencyCaseService — never a manual insert.

    Expects: $action
--}}
<div class="alert alert-warning py-2 px-3 small">
    <div>{{ __('maternity_handoffs.escalation.flag_created_nothing') }}</div>
    <div class="fw-semibold">{{ __('maternity_handoffs.escalation.case_will_be_created') }}</div>
</div>

@unless ($action->reusesExistingRecord())
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="{{ $action->modalId }}Arrival">
                {{ __('maternity_handoffs.modal.arrival_mode') }}
            </label>
            <select name="arrival_mode" id="{{ $action->modalId }}Arrival"
                    class="form-select @error('arrival_mode') is-invalid @enderror">
                @foreach (['TRANSFER_FROM_WARD', 'TRANSFER_FROM_OPD', 'WALK_IN', 'AMBULANCE', 'UNKNOWN'] as $mode)
                    <option value="{{ $mode }}" @selected(old('arrival_mode', 'TRANSFER_FROM_WARD') === $mode)>
                        {{ __('maternity_handoffs.arrival_modes.'.strtolower($mode)) }}
                    </option>
                @endforeach
            </select>
            @error('arrival_mode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="{{ $action->modalId }}Complaint">
                {{ __('maternity_handoffs.modal.chief_complaint') }}
            </label>
            <input type="text" name="chief_complaint" id="{{ $action->modalId }}Complaint"
                   maxlength="500" value="{{ old('chief_complaint') }}"
                   class="form-control @error('chief_complaint') is-invalid @enderror">
            @error('chief_complaint')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>
@endunless

<div class="alert alert-light border py-2 px-3 small mt-3 mb-0 text-muted">
    <div>{{ __('maternity_handoffs.escalation.no_admission_request_created') }}</div>
    <div>{{ __('maternity_handoffs.escalation.no_theatre_case_created') }}</div>
</div>
