@php
    $selectedDangerSigns = old('danger_signs', $ancVisit?->danger_signs ?? []);
    $selectedRiskFlags = old('risk_flags', $ancVisit?->risk_flags ?? []);
    $selectedSupplements = old('supplements', $ancVisit?->supplements ?? []);
    $selectedImmunisations = old('immunisations', $ancVisit?->immunisations ?? []);
@endphp
<div class="card">
    <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('maternity.anc_visit') }}</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">{{ __('maternity.visit_date') }}</label><input type="datetime-local" name="visit_date" class="form-control" required value="{{ old('visit_date', optional($ancVisit?->visit_date)->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i')) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.visit_number') }}</label><input type="number" min="1" name="visit_number" class="form-control" value="{{ old('visit_number', $ancVisit?->visit_number) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.ga_weeks') }}</label><input type="number" min="0" max="45" name="gestational_age_weeks" class="form-control" value="{{ old('gestational_age_weeks', $ancVisit?->gestational_age_weeks ?? $profile->gestational_age_weeks) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.ga_days') }}</label><input type="number" min="0" max="6" name="gestational_age_days" class="form-control" value="{{ old('gestational_age_days', $ancVisit?->gestational_age_days ?? $profile->gestational_age_days) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.related_case') }}</label><select name="maternity_case_id" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($maternityCases as $case)<option value="{{ $case->id }}" @selected(old('maternity_case_id', $ancVisit?->maternity_case_id) == $case->id)>{{ $case->case_type?->label() }} #{{ $case->id }}</option>@endforeach</select></div>

            <div class="col-md-2"><label class="form-label">{{ __('maternity.weight') }}</label><input type="number" step="0.01" name="weight_kg" class="form-control" value="{{ old('weight_kg', $ancVisit?->weight_kg) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.bp_systolic') }}</label><input type="number" name="blood_pressure_systolic" class="form-control" value="{{ old('blood_pressure_systolic', $ancVisit?->blood_pressure_systolic) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.bp_diastolic') }}</label><input type="number" name="blood_pressure_diastolic" class="form-control" value="{{ old('blood_pressure_diastolic', $ancVisit?->blood_pressure_diastolic) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.pulse') }}</label><input type="number" name="pulse" class="form-control" value="{{ old('pulse', $ancVisit?->pulse) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.temperature') }}</label><input type="number" step="0.1" name="temperature" class="form-control" value="{{ old('temperature', $ancVisit?->temperature) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.respiratory_rate') }}</label><input type="number" name="respiratory_rate" class="form-control" value="{{ old('respiratory_rate', $ancVisit?->respiratory_rate) }}"></div>

            <div class="col-md-3"><label class="form-label">{{ __('maternity.fundal_height') }}</label><input type="number" step="0.01" name="fundal_height_cm" class="form-control" value="{{ old('fundal_height_cm', $ancVisit?->fundal_height_cm) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.fetal_heart_rate') }}</label><input type="number" name="fetal_heart_rate" class="form-control" value="{{ old('fetal_heart_rate', $ancVisit?->fetal_heart_rate) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.fetal_movement') }}</label><input name="fetal_movement" class="form-control" value="{{ old('fetal_movement', $ancVisit?->fetal_movement) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.presentation') }}</label><select name="presentation" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($presentations as $presentation)<option value="{{ $presentation->value }}" @selected(old('presentation', $ancVisit?->presentation?->value) === $presentation->value)>{{ $presentation->label() }}</option>@endforeach</select></div>

            <div class="col-md-3"><label class="form-label">{{ __('maternity.urine_protein') }}</label><select name="urine_protein" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($urineProteinResults as $result)<option value="{{ $result->value }}" @selected(old('urine_protein', $ancVisit?->urine_protein?->value) === $result->value)>{{ $result->label() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.urine_glucose') }}</label><select name="urine_glucose" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($urineGlucoseResults as $result)<option value="{{ $result->value }}" @selected(old('urine_glucose', $ancVisit?->urine_glucose?->value) === $result->value)>{{ $result->label() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.oedema') }}</label><input name="oedema" class="form-control" value="{{ old('oedema', $ancVisit?->oedema) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.haemoglobin') }}</label><input type="number" step="0.1" name="haemoglobin" class="form-control" value="{{ old('haemoglobin', $ancVisit?->haemoglobin) }}"></div>

            <div class="col-md-6">
                <label class="form-label">{{ __('maternity.danger_signs') }}</label>
                <div class="border rounded p-2" style="max-height:220px;overflow:auto;">
                    @foreach($dangerSigns as $sign)
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="danger_signs[]" value="{{ $sign->value }}" id="danger_{{ $sign->value }}" @checked(in_array($sign->value, $selectedDangerSigns, true))><label class="form-check-label" for="danger_{{ $sign->value }}">{{ $sign->label() }}</label></div>
                    @endforeach
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('maternity.risk_flags') }}</label>
                <div class="border rounded p-2" style="max-height:220px;overflow:auto;">
                    @foreach($riskFlags as $flag)
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="risk_flags[]" value="{{ $flag->value }}" id="risk_{{ $flag->value }}" @checked(in_array($flag->value, $selectedRiskFlags, true))><label class="form-check-label" for="risk_{{ $flag->value }}">{{ $flag->label() }}</label></div>
                    @endforeach
                </div>
            </div>

            <div class="col-md-4"><label class="form-label">{{ __('maternity.assessment') }}</label><textarea name="assessment" rows="3" class="form-control">{{ old('assessment', $ancVisit?->assessment) }}</textarea></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.plan') }}</label><textarea name="plan" rows="3" class="form-control">{{ old('plan', $ancVisit?->plan) }}</textarea></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.counselling') }}</label><textarea name="counselling" rows="3" class="form-control">{{ old('counselling', $ancVisit?->counselling) }}</textarea></div>

            <div class="col-md-6">
                <label class="form-label">{{ __('maternity.supplements') }}</label>
                @foreach($supplementOptions as $option)
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="supplements[]" value="{{ $option }}" id="supplement_{{ $option }}" @checked(in_array($option, $selectedSupplements, true))><label class="form-check-label" for="supplement_{{ $option }}">{{ __('maternity.supplement_options.'.$option) }}</label></div>
                @endforeach
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('maternity.immunisations') }}</label>
                @foreach($immunisationOptions as $option)
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="immunisations[]" value="{{ $option }}" id="immunisation_{{ $option }}" @checked(in_array($option, $selectedImmunisations, true))><label class="form-check-label" for="immunisation_{{ $option }}">{{ __('maternity.immunisation_options.'.$option) }}</label></div>
                @endforeach
            </div>

            <div class="col-md-3"><label class="form-label">{{ __('maternity.next_visit_date') }}</label><input type="date" name="next_visit_date" class="form-control" value="{{ old('next_visit_date', $ancVisit?->next_visit_date?->format('Y-m-d')) }}"></div>
            <div class="col-md-3"><label class="form-label">{{ __('maternity.referral_type') }}</label><select name="referral_type" class="form-select">@foreach($referralTypes as $type)<option value="{{ $type->value }}" @selected(old('referral_type', $ancVisit?->referral_type?->value ?? 'none') === $type->value)>{{ $type->label() }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">{{ __('maternity.referral_reason') }}</label><input name="referral_reason" class="form-control" value="{{ old('referral_reason', $ancVisit?->referral_reason) }}"></div>
        </div>
    </div>
    <div class="card-footer text-end"><a href="{{ route('admin.maternity.pregnancies.show', $profile) }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a><button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('common.save') }}</button></div>
</div>
