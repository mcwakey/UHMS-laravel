{{--
    Phase 14R.5.1 — Gynaecology → Obstetrics/Maternity referral.

    The current Gynaecology route, its specialty and every entry stay exactly as
    they are. A separate target route is created through the existing
    ConsultationRouteService; no referral subsystem is introduced.

    Expects: $action
--}}
<div class="mb-2">
    <label class="form-label" for="{{ $action->modalId }}Notes">
        {{ __('maternity_handoffs.modal.referral_notes') }}
    </label>
    <textarea name="notes" id="{{ $action->modalId }}Notes" rows="3" maxlength="1000"
              class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
    @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>

<div class="alert alert-light border py-2 px-3 small mb-0">
    <div>{{ __('maternity_handoffs.consultation.remains_gynaecology') }}</div>
    <div class="text-muted">{{ __('maternity_handoffs.fallback.no_maternity_record_created') }}</div>
</div>
