@php($selectedComplications = old('complications', $record?->complications ?? []))
<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.delivery_record') }}</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">{{ __('maternity.delivery_at') }}</label><input type="datetime-local" name="delivery_at" class="form-control" value="{{ old('delivery_at', optional($record?->delivery_at)->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i')) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.delivery_mode') }}</label><select name="delivery_mode" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($deliveryModes as $mode)<option value="{{ $mode->value }}" @selected(old('delivery_mode', $record?->delivery_mode?->value) === $mode->value)>{{ $mode->label() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.delivery_outcome') }}</label><select name="delivery_outcome" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($deliveryOutcomes as $outcome)<option value="{{ $outcome->value }}" @selected(old('delivery_outcome', $record?->delivery_outcome?->value) === $outcome->value)>{{ $outcome->label() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.status') }}</label><select name="status" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $record?->status?->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>

            <div class="col-md-3"><label class="form-label">{{ __('maternity.placenta_status') }}</label><select name="placenta_status" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($placentaStatuses as $status)<option value="{{ $status->value }}" @selected(old('placenta_status', $record?->placenta_status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.estimated_blood_loss') }}</label><input type="number" name="estimated_blood_loss_ml" class="form-control" value="{{ old('estimated_blood_loss_ml', $record?->estimated_blood_loss_ml) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.maternal_condition') }}</label><select name="maternal_condition" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($maternalConditions as $condition)<option value="{{ $condition->value }}" @selected(old('maternal_condition', $record?->maternal_condition?->value) === $condition->value)>{{ $condition->label() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.attending_staff') }}</label><select name="attending_staff_id" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($staff as $member)<option value="{{ $member->id }}" @selected(old('attending_staff_id', $record?->attending_staff_id) == $member->id)>{{ $member->name }}</option>@endforeach</select></div>

            <div class="col-md-3"><label class="form-label">{{ __('maternity.newborn_count') }}</label><input type="number" min="0" max="10" name="newborn_count" class="form-control" value="{{ old('newborn_count', $record?->newborn_count ?? 1) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.theatre_escalation') }}</label><input type="number" name="theatre_case_id" class="form-control" value="{{ old('theatre_case_id', $record?->theatre_case_id) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.emergency_escalation') }}</label><input type="number" name="emergency_case_id" class="form-control" value="{{ old('emergency_case_id', $record?->emergency_case_id) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.complications') }}</label><input name="complications[]" class="form-control" value="{{ $selectedComplications[0] ?? '' }}"></div>

            <div class="col-12"><label class="form-label">{{ __('maternity.notes') }}</label><textarea name="notes" rows="4" class="form-control">{{ old('notes', $record?->notes) }}</textarea></div>
        </div>
    </div>
    <div class="card-footer text-end"><a href="{{ route('admin.maternity.labor.show', $episode) }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a><button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('common.save') }}</button></div>
</div>
