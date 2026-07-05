@php
    $selectedDangerSigns = old('danger_signs', $observation?->danger_signs ?? []);
    $selectedRiskFlags = old('risk_flags', $observation?->risk_flags ?? []);
@endphp
<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.labor_observation') }}</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">{{ __('maternity.observed_at') }}</label><input type="datetime-local" name="observed_at" class="form-control" required value="{{ old('observed_at', optional($observation?->observed_at)->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i')) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.labor_stage') }}</label><select name="labor_stage" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($stages as $stage)<option value="{{ $stage->value }}" @selected(old('labor_stage', $observation?->labor_stage?->value ?? $episode->labor_stage?->value) === $stage->value)>{{ $stage->label() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.cervical_dilation') }}</label><input type="number" step="0.1" name="cervical_dilation_cm" class="form-control" value="{{ old('cervical_dilation_cm', $observation?->cervical_dilation_cm) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.fetal_heart_rate') }}</label><input type="number" name="fetal_heart_rate" class="form-control" value="{{ old('fetal_heart_rate', $observation?->fetal_heart_rate) }}"></div>

            <div class="col-md-3"><label class="form-label">{{ __('maternity.contractions_per_10_min') }}</label><input type="number" name="contractions_per_10_min" class="form-control" value="{{ old('contractions_per_10_min', $observation?->contractions_per_10_min) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.contraction_duration') }}</label><input type="number" name="contraction_duration_seconds" class="form-control" value="{{ old('contraction_duration_seconds', $observation?->contraction_duration_seconds) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.descent') }}</label><input name="descent" class="form-control" value="{{ old('descent', $observation?->descent) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.moulding') }}</label><input name="moulding" class="form-control" value="{{ old('moulding', $observation?->moulding) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.caput') }}</label><input name="caput" class="form-control" value="{{ old('caput', $observation?->caput) }}"></div>

            <div class="col-md-3"><label class="form-label">{{ __('maternity.membranes_status') }}</label><select name="membranes_status" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($membranesStatuses as $status)<option value="{{ $status->value }}" @selected(old('membranes_status', $observation?->membranes_status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.liquor_colour') }}</label><select name="liquor_colour" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($liquorColours as $colour)<option value="{{ $colour->value }}" @selected(old('liquor_colour', $observation?->liquor_colour?->value) === $colour->value)>{{ $colour->label() }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.maternal_pulse') }}</label><input type="number" name="maternal_pulse" class="form-control" value="{{ old('maternal_pulse', $observation?->maternal_pulse) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.bp_systolic') }}</label><input type="number" name="blood_pressure_systolic" class="form-control" value="{{ old('blood_pressure_systolic', $observation?->blood_pressure_systolic) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.bp_diastolic') }}</label><input type="number" name="blood_pressure_diastolic" class="form-control" value="{{ old('blood_pressure_diastolic', $observation?->blood_pressure_diastolic) }}"></div>

            <div class="col-md-2"><label class="form-label">{{ __('maternity.temperature') }}</label><input type="number" step="0.1" name="temperature" class="form-control" value="{{ old('temperature', $observation?->temperature) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.respiratory_rate') }}</label><input type="number" name="respiratory_rate" class="form-control" value="{{ old('respiratory_rate', $observation?->respiratory_rate) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.urine_protein') }}</label><select name="urine_protein" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($urineProteinResults as $result)<option value="{{ $result->value }}" @selected(old('urine_protein', $observation?->urine_protein?->value) === $result->value)>{{ $result->label() }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.urine_glucose') }}</label><select name="urine_glucose" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($urineGlucoseResults as $result)<option value="{{ $result->value }}" @selected(old('urine_glucose', $observation?->urine_glucose?->value) === $result->value)>{{ $result->label() }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.urine_volume') }}</label><input type="number" name="urine_volume_ml" class="form-control" value="{{ old('urine_volume_ml', $observation?->urine_volume_ml) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.pain_score') }}</label><input type="number" min="0" max="10" name="pain_score" class="form-control" value="{{ old('pain_score', $observation?->pain_score) }}"></div>

            <div class="col-md-4"><label class="form-label">{{ __('maternity.oxytocin') }}</label><textarea name="oxytocin" rows="2" class="form-control">{{ old('oxytocin', $observation?->oxytocin) }}</textarea></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.fluids') }}</label><textarea name="fluids" rows="2" class="form-control">{{ old('fluids', $observation?->fluids) }}</textarea></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.medication') }}</label><textarea name="medication" rows="2" class="form-control">{{ old('medication', $observation?->medication) }}</textarea></div>

            <div class="col-md-6"><label class="form-label">{{ __('maternity.danger_signs') }}</label><div class="border rounded p-2" style="max-height:220px;overflow:auto;">@foreach($dangerSigns as $sign)<div class="form-check"><input class="form-check-input" type="checkbox" name="danger_signs[]" value="{{ $sign->value }}" id="labor_danger_{{ $sign->value }}" @checked(in_array($sign->value, $selectedDangerSigns, true))><label class="form-check-label" for="labor_danger_{{ $sign->value }}">{{ $sign->label() }}</label></div>@endforeach</div></div>
            <div class="col-md-6"><label class="form-label">{{ __('maternity.risk_flags') }}</label><div class="border rounded p-2" style="max-height:220px;overflow:auto;">@foreach($riskFlags as $flag)<div class="form-check"><input class="form-check-input" type="checkbox" name="risk_flags[]" value="{{ $flag->value }}" id="labor_risk_{{ $flag->value }}" @checked(in_array($flag->value, $selectedRiskFlags, true))><label class="form-check-label" for="labor_risk_{{ $flag->value }}">{{ $flag->label() }}</label></div>@endforeach</div></div>

            <div class="col-12"><label class="form-label">{{ __('maternity.notes') }}</label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $observation?->notes) }}</textarea></div>
        </div>
    </div>
    <div class="card-footer text-end"><a href="{{ route('admin.maternity.labor.show', $episode) }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a><button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('common.save') }}</button></div>
</div>
