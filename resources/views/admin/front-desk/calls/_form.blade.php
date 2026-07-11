@php
    $log = $log ?? null;
    $isEdit = $log !== null;
    $followStatuses = ['pending', 'completed', 'cancelled'];
@endphp

@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ $isEdit ? route('admin.front-desk.calls.update', $log) : route('admin.front-desk.calls.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.calls.section_call') }}</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('front_desk.fields.direction') }} <span class="text-danger">*</span></label>
                            <select name="direction" class="form-select" required>
                                @foreach($directions as $d)
                                <option value="{{ $d->value }}" @selected(old('direction', $log?->direction?->value) === $d->value)>{{ $d->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('front_desk.fields.category') }} <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                @foreach($categories as $c)
                                <option value="{{ $c->value }}" @selected(old('category', $log?->category?->value) === $c->value)>{{ $c->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('front_desk.fields.outcome') }} <span class="text-danger">*</span></label>
                            <select name="outcome" class="form-select" required>
                                @foreach($outcomes as $o)
                                <option value="{{ $o->value }}" @selected(old('outcome', $log?->outcome?->value) === $o->value)>{{ $o->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.caller_name') }}</label>
                            <input type="text" name="caller_name" class="form-control" value="{{ old('caller_name', $log?->caller_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.recipient_name') }}</label>
                            <input type="text" name="recipient_name" class="form-control" value="{{ old('recipient_name', $log?->recipient_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.phone_number') }}</label>
                            <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $log?->phone_number) }}">
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
                            <label class="form-label">{{ __('front_desk.fields.related_patient') }}</label>
                            <select name="related_patient_id" class="form-select select2">
                                <option value="">{{ __('front_desk.none') }}</option>
                                @foreach($patients as $p)
                                <option value="{{ $p->id }}" @selected((int) old('related_patient_id', $log?->related_patient_id) === $p->id)>{{ $p->patient_number }} — {{ $p->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('front_desk.fields.started_at') }}</label>
                            <input type="datetime-local" name="started_at" class="form-control" value="{{ old('started_at', optional($log?->started_at)->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('front_desk.fields.ended_at') }}</label>
                            <input type="datetime-local" name="ended_at" class="form-control" value="{{ old('ended_at', optional($log?->ended_at)->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('front_desk.fields.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $log?->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.calls.section_followup') }}</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="follow_up_required" value="0">
                        <input type="checkbox" name="follow_up_required" value="1" class="form-check-input" id="follow_up_required" @checked(old('follow_up_required', $log?->follow_up_required))>
                        <label class="form-check-label" for="follow_up_required">{{ __('front_desk.fields.follow_up_required') }}</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('front_desk.fields.follow_up_status') }}</label>
                        <select name="follow_up_status" class="form-select">
                            <option value="">{{ __('front_desk.none') }}</option>
                            @foreach($followStatuses as $fs)
                            <option value="{{ $fs }}" @selected(old('follow_up_status', $log?->follow_up_status) === $fs)>{{ __('front_desk.follow_up_status.' . $fs) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('front_desk.fields.assigned_follow_up_user') }}</label>
                        <select name="assigned_follow_up_user_id" class="form-select select2">
                            <option value="">{{ __('front_desk.none') }}</option>
                            @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected((int) old('assigned_follow_up_user_id', $log?->assigned_follow_up_user_id) === $u->id)>{{ $u->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="ti ti-device-floppy me-1"></i>{{ __('front_desk.actions.save') }}</button>
                        <a href="{{ $isEdit ? route('admin.front-desk.calls.show', $log) : route('admin.front-desk.calls.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
