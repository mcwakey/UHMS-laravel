@php
    $log = $log ?? null;
    $isEdit = $log !== null;
    use App\Enums\FrontDesk\VisitorStatus;
    $statusChoices = $isEdit ? $statuses : [VisitorStatus::CHECKED_IN, VisitorStatus::DENIED];
@endphp

@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

@include('admin.front-desk.partials.visitor-warnings', ['warnings' => $warnings ?? []])

<form method="POST" action="{{ $isEdit ? route('admin.front-desk.visitors.update', $log) : route('admin.front-desk.visitors.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.visitors.section_visitor') }}</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.visitor_context') }} <span class="text-danger">*</span></label>
                            <select name="visitor_context" class="form-select" required>
                                @foreach($contexts as $ctx)
                                <option value="{{ $ctx->value }}" @selected(old('visitor_context', $log?->visitor_context?->value) === $ctx->value)>{{ $ctx->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.visitor_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="visitor_name" class="form-control" value="{{ old('visitor_name', $log?->visitor_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.visitor_phone') }}</label>
                            <input type="text" name="visitor_phone" class="form-control" value="{{ old('visitor_phone', $log?->visitor_phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.organization') }}</label>
                            <input type="text" name="organization" class="form-control" value="{{ old('organization', $log?->organization) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.id_type') }}</label>
                            <input type="text" name="id_type" class="form-control" value="{{ old('id_type', $log?->id_type) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.id_number') }}</label>
                            <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $log?->id_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.badge_number') }}</label>
                            <input type="text" name="badge_number" class="form-control" value="{{ old('badge_number', $log?->badge_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.vehicle_number') }}</label>
                            <input type="text" name="vehicle_number" class="form-control" value="{{ old('vehicle_number', $log?->vehicle_number) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.visitors.section_link') }}</h5></div>
                <div class="card-body">
                    <p class="text-muted fs-13"><i class="ti ti-shield-lock me-1"></i>{{ __('front_desk.privacy.safe_notice') }}</p>
                    <div class="row g-3">
                        @php($selectedPatientId = (int) old('patient_id', $log?->patient_id ?? ($preselectedPatient?->id ?? 0)))
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.patient') }}</label>
                            <select name="patient_id" class="form-select select2">
                                <option value="">{{ __('front_desk.none') }}</option>
                                @if($preselectedPatient ?? null)
                                    <option value="{{ $preselectedPatient->id }}" @selected($selectedPatientId === $preselectedPatient->id)>{{ $preselectedPatient->patient_number }} — {{ $preselectedPatient->full_name }}</option>
                                @endif
                                @foreach($patients as $p)
                                <option value="{{ $p->id }}" @selected($selectedPatientId === $p->id)>{{ $p->patient_number }} — {{ $p->full_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('front_desk.visitors.active_admission') }} {{ __('front_desk.privacy.safe_notice') }}</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.ward') }}</label>
                            <select name="ward_id" class="form-select">
                                <option value="">{{ __('front_desk.none') }}</option>
                                @foreach($wards as $w)
                                <option value="{{ $w->id }}" @selected((int) old('ward_id', $log?->ward_id) === $w->id)>{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.department') }}</label>
                            <select name="department_id" class="form-select">
                                <option value="">{{ __('front_desk.none') }}</option>
                                @foreach($departments as $d)
                                <option value="{{ $d->id }}" @selected((int) old('department_id', $log?->department_id) === $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.relationship_to_patient') }}</label>
                            <input type="text" name="relationship_to_patient" class="form-control" value="{{ old('relationship_to_patient', $log?->relationship_to_patient) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.person_to_see') }}</label>
                            <input type="text" name="person_to_see" class="form-control" value="{{ old('person_to_see', $log?->person_to_see) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('front_desk.fields.purpose') }}</label>
                            <textarea name="purpose" class="form-control" rows="2">{{ old('purpose', $log?->purpose) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.visitors.section_meta') }}</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('front_desk.fields.time_in') }}</label>
                        <input type="datetime-local" name="time_in" class="form-control" value="{{ old('time_in', optional($log?->time_in)->format('Y-m-d\TH:i')) }}">
                    </div>
                    @if($isEdit)
                    <div class="mb-3">
                        <label class="form-label">{{ __('front_desk.fields.time_out') }}</label>
                        <input type="datetime-local" name="time_out" class="form-control" value="{{ old('time_out', optional($log?->time_out)->format('Y-m-d\TH:i')) }}">
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">{{ __('front_desk.fields.status') }} <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            @foreach($statusChoices as $s)
                            <option value="{{ $s->value }}" @selected(old('status', $log?->status?->value ?? VisitorStatus::CHECKED_IN->value) === $s->value)>{{ $s->translatedLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('front_desk.fields.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $log?->notes) }}</textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="ti ti-device-floppy me-1"></i>{{ __('front_desk.actions.save') }}</button>
                        <a href="{{ $isEdit ? route('admin.front-desk.visitors.show', $log) : route('admin.front-desk.visitors.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
