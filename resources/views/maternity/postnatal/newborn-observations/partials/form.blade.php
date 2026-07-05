@csrf
@if($observation) @method('PATCH') @endif
<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.newborn_observation') }} · {{ __('maternity.birth_order') }} {{ $newborn->birth_order }}</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">{{ __('maternity.observed_at') }}</label><input type="datetime-local" name="observed_at" class="form-control" value="{{ old('observed_at', $observation?->observed_at?->format('Y-m-d\\TH:i')) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.temperature') }}</label><input type="number" step="0.1" name="temperature" class="form-control" value="{{ old('temperature', $observation?->temperature) }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('maternity.weight') }}</label><input type="number" step="0.01" name="weight_kg" class="form-control" value="{{ old('weight_kg', $observation?->weight_kg) }}"></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.feeding_status') }}</label><select name="feeding_status" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($feedingStatuses as $status)<option value="{{ $status->value }}" @selected(old('feeding_status', $observation?->feeding_status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.breathing_status') }}</label><select name="breathing_status" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($breathingStatuses as $status)<option value="{{ $status->value }}" @selected(old('breathing_status', $observation?->breathing_status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.cord_status') }}</label><select name="cord_status" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($cordStatuses as $status)<option value="{{ $status->value }}" @selected(old('cord_status', $observation?->cord_status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.jaundice_status') }}</label><select name="jaundice_status" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($jaundiceStatuses as $status)<option value="{{ $status->value }}" @selected(old('jaundice_status', $observation?->jaundice_status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.stooling') }}</label><input name="stooling" class="form-control" value="{{ old('stooling', $observation?->stooling) }}"></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.urination') }}</label><input name="urination" class="form-control" value="{{ old('urination', $observation?->urination) }}"></div>
            <div class="col-md-4"><label class="form-label">{{ __('maternity.activity') }}</label><input name="activity" class="form-control" value="{{ old('activity', $observation?->activity) }}"></div>
            <div class="col-md-6">
                <label class="form-label">{{ __('maternity.newborn_danger_signs') }}</label>
                <div class="d-flex flex-wrap gap-2">@foreach($dangerSigns as $sign)<label class="badge bg-light text-dark border"><input type="checkbox" name="danger_signs[]" value="{{ $sign->value }}" @checked(in_array($sign->value, old('danger_signs', $observation?->danger_signs ?? []), true))> {{ $sign->label() }}</label>@endforeach</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('maternity.newborn_risk_flags') }}</label>
                <div class="d-flex flex-wrap gap-2">@foreach($riskFlags as $flag)<label class="badge bg-light text-dark border"><input type="checkbox" name="risk_flags[]" value="{{ $flag->value }}" @checked(in_array($flag->value, old('risk_flags', $observation?->risk_flags ?? []), true))> {{ $flag->label() }}</label>@endforeach</div>
            </div>
            <div class="col-12"><label class="form-label">{{ __('maternity.immunisation_note') }}</label><textarea name="immunisation_note" rows="2" class="form-control">{{ old('immunisation_note', $observation?->immunisation_note) }}</textarea></div>
            <div class="col-12"><label class="form-label">{{ __('maternity.assessment') }}</label><textarea name="assessment" rows="3" class="form-control">{{ old('assessment', $observation?->assessment) }}</textarea></div>
            <div class="col-12"><label class="form-label">{{ __('maternity.plan') }}</label><textarea name="plan" rows="3" class="form-control">{{ old('plan', $observation?->plan) }}</textarea></div>
            <div class="col-12"><label class="form-label">{{ __('maternity.counselling') }}</label><textarea name="counselling" rows="3" class="form-control">{{ old('counselling', $observation?->counselling) }}</textarea></div>
        </div>
    </div>
    <div class="card-footer text-end"><button class="btn btn-primary">{{ __('common.save') }}</button></div>
</div>
