@php $log = $log ?? null; $isEdit = $log !== null; @endphp
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $isEdit ? route('admin.front-desk.incidents.update', $log) : route('admin.front-desk.incidents.store') }}">
    @csrf @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.incidents.section_incident') }}</h5></div>
                <div class="card-body"><div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('front_desk.fields.incident_type') }} <span class="text-danger">*</span></label>
                        <select name="incident_type" class="form-select" required>@foreach($types as $t)<option value="{{ $t->value }}" @selected(old('incident_type', $log?->incident_type?->value) === $t->value)>{{ $t->translatedLabel() }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('front_desk.fields.severity') }} <span class="text-danger">*</span></label>
                        <select name="severity" class="form-select" required>@foreach($severities as $s)<option value="{{ $s->value }}" @selected(old('severity', $log?->severity?->value ?? 'low') === $s->value)>{{ $s->translatedLabel() }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('front_desk.fields.started_at') }}</label>
                        <input type="datetime-local" name="reported_at" class="form-control" value="{{ old('reported_at', optional($log?->reported_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-6"><label class="form-label">{{ __('front_desk.fields.location') }}</label><input type="text" name="location" class="form-control" value="{{ old('location', $log?->location) }}"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('front_desk.fields.department') }}</label>
                        <select name="department_id" class="form-select"><option value="">{{ __('front_desk.none') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected((int) old('department_id', $log?->department_id) === $d->id)>{{ $d->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">{{ __('front_desk.fields.reporter_name') }}</label><input type="text" name="reported_by_name" class="form-control" value="{{ old('reported_by_name', $log?->reported_by_name) }}"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('front_desk.fields.reporter_phone') }}</label><input type="text" name="reported_by_phone" class="form-control" value="{{ old('reported_by_phone', $log?->reported_by_phone) }}"></div>
                    <div class="col-12"><label class="form-label">{{ __('front_desk.fields.description') }} <span class="text-danger">*</span></label><textarea name="description" class="form-control" rows="3" required>{{ old('description', $log?->description) }}</textarea></div>
                    <div class="col-12"><label class="form-label">{{ __('front_desk.fields.action_taken') }}</label><textarea name="action_taken" class="form-control" rows="2">{{ old('action_taken', $log?->action_taken) }}</textarea></div>
                </div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.incidents.section_links') }}</h5></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.related_visitor') }}</label>
                        <select name="related_visitor_log_id" class="form-select"><option value="">{{ __('front_desk.none') }}</option>@foreach($recentVisitors as $v)<option value="{{ $v->id }}" @selected((int) old('related_visitor_log_id', $log?->related_visitor_log_id) === $v->id)>#{{ $v->id }} — {{ $v->visitor_name }}</option>@endforeach</select></div>
                    <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.related_call') }}</label>
                        <select name="related_call_log_id" class="form-select"><option value="">{{ __('front_desk.none') }}</option>@foreach($recentCalls as $c)<option value="{{ $c->id }}" @selected((int) old('related_call_log_id', $log?->related_call_log_id) === $c->id)>#{{ $c->id }} — {{ $c->caller_name ?: $c->recipient_name }}</option>@endforeach</select></div>
                    <div class="mb-3"><label class="form-label">{{ __('front_desk.fields.related_courier') }}</label>
                        <select name="related_courier_log_id" class="form-select"><option value="">{{ __('front_desk.none') }}</option>@foreach($recentCouriers as $co)<option value="{{ $co->id }}" @selected((int) old('related_courier_log_id', $log?->related_courier_log_id) === $co->id)>#{{ $co->id }} — {{ $co->tracking_number ?: $co->recipient_name }}</option>@endforeach</select></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="ti ti-device-floppy me-1"></i>{{ __('front_desk.actions.save') }}</button>
                        <a href="{{ $isEdit ? route('admin.front-desk.incidents.show', $log) : route('admin.front-desk.incidents.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
