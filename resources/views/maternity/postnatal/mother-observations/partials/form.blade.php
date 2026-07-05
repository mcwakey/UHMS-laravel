@csrf
@if($observation) @method('PATCH') @endif
<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.mother_observation') }}</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">{{ __('maternity.observed_at') }}</label><input type="datetime-local" name="observed_at" class="form-control" value="{{ old('observed_at', $observation?->observed_at?->format('Y-m-d\\TH:i')) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.bp_systolic') }}</label><input type="number" name="blood_pressure_systolic" class="form-control" value="{{ old('blood_pressure_systolic', $observation?->blood_pressure_systolic) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.bp_diastolic') }}</label><input type="number" name="blood_pressure_diastolic" class="form-control" value="{{ old('blood_pressure_diastolic', $observation?->blood_pressure_diastolic) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.pulse') }}</label><input type="number" name="pulse" class="form-control" value="{{ old('pulse', $observation?->pulse) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.temperature') }}</label><input type="number" step="0.1" name="temperature" class="form-control" value="{{ old('temperature', $observation?->temperature) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.respiratory_rate') }}</label><input type="number" name="respiratory_rate" class="form-control" value="{{ old('respiratory_rate', $observation?->respiratory_rate) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.bleeding_status') }}</label><select name="bleeding_status" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($bleedingStatuses as $status)<option value="{{ $status->value }}" @selected(old('bleeding_status', $observation?->bleeding_status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.uterus_condition') }}</label><select name="uterus_condition" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($uterusConditions as $condition)<option value="{{ $condition->value }}" @selected(old('uterus_condition', $observation?->uterus_condition?->value) === $condition->value)>{{ $condition->label() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.pain_score') }}</label><input type="number" min="0" max="10" name="pain_score" class="form-control" value="{{ old('pain_score', $observation?->pain_score) }}"></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.wound_condition') }}</label><select name="wound_condition" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($woundConditions as $condition)<option value="{{ $condition->value }}" @selected(old('wound_condition', $observation?->wound_condition?->value) === $condition->value)>{{ $condition->label() }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.breastfeeding_status') }}</label><select name="breastfeeding_status" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($breastfeedingStatuses as $status)<option value="{{ $status->value }}" @selected(old('breastfeeding_status', $observation?->breastfeeding_status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.mobility') }}</label><input name="mobility" class="form-control" value="{{ old('mobility', $observation?->mobility) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.urination') }}</label><input name="urination" class="form-control" value="{{ old('urination', $observation?->urination) }}"></div>
            <div class="col-12"><label class="form-label">{{ __('maternity.mental_wellbeing_note') }}</label><textarea name="mental_wellbeing_note" rows="2" class="form-control">{{ old('mental_wellbeing_note', $observation?->mental_wellbeing_note) }}</textarea></div>
            <div class="col-md-6">
                <label class="form-label">{{ __('maternity.mother_danger_signs') }}</label>
                <div class="d-flex flex-wrap gap-2">@foreach($dangerSigns as $sign)<label class="badge bg-light text-dark border"><input type="checkbox" name="danger_signs[]" value="{{ $sign->value }}" @checked(in_array($sign->value, old('danger_signs', $observation?->danger_signs ?? []), true))> {{ $sign->label() }}</label>@endforeach</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('maternity.mother_risk_flags') }}</label>
                <div class="d-flex flex-wrap gap-2">@foreach($riskFlags as $flag)<label class="badge bg-light text-dark border"><input type="checkbox" name="risk_flags[]" value="{{ $flag->value }}" @checked(in_array($flag->value, old('risk_flags', $observation?->risk_flags ?? []), true))> {{ $flag->label() }}</label>@endforeach</div>
            </div>
            <div class="col-12"><label class="form-label">{{ __('maternity.assessment') }}</label><textarea name="assessment" rows="3" class="form-control">{{ old('assessment', $observation?->assessment) }}</textarea></div>
            <div class="col-12"><label class="form-label">{{ __('maternity.plan') }}</label><textarea name="plan" rows="3" class="form-control">{{ old('plan', $observation?->plan) }}</textarea></div>
            <div class="col-12"><label class="form-label">{{ __('maternity.counselling') }}</label><textarea name="counselling" rows="3" class="form-control">{{ old('counselling', $observation?->counselling) }}</textarea></div>
        </div>
    </div>
    <div class="card-footer text-end"><button class="btn btn-primary">{{ __('common.save') }}</button></div>
</div>
