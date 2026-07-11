@php $log = $log ?? null; $isEdit = $log !== null; @endphp
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $isEdit ? route('admin.front-desk.handovers.update', $log) : route('admin.front-desk.handovers.store') }}">
    @csrf @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.handovers.section_staff') }}</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.shift_date') }}</label>
                            <input type="date" name="shift_date" class="form-control" value="{{ old('shift_date', optional($log?->shift_date)->format('Y-m-d') ?? now()->toDateString()) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.shift_name') }}</label>
                            <input type="text" name="shift_name" class="form-control" value="{{ old('shift_name', $log?->shift_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.incoming_user') }}</label>
                            <select name="incoming_user_id" class="form-select select2">
                                <option value="">{{ __('front_desk.none') }}</option>
                                @foreach($users as $u)<option value="{{ $u->id }}" @selected((int) old('incoming_user_id', $log?->incoming_user_id) === $u->id)>{{ $u->full_name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.department') }}</label>
                            <select name="department_id" class="form-select">
                                <option value="">{{ __('front_desk.none') }}</option>
                                @foreach($departments as $d)<option value="{{ $d->id }}" @selected((int) old('department_id', $log?->department_id) === $d->id)>{{ $d->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('front_desk.fields.summary_notes') }}</label>
                            <textarea name="summary_notes" class="form-control" rows="4">{{ old('summary_notes', $log?->summary_notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            @isset($snapshot)
            <div class="card">
                <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.handovers.section_snapshot') }}</h5></div>
                <div class="card-body">
                    <p class="text-muted fs-13">{{ __('front_desk.handovers.snapshot_hint') }}</p>
                    <dl class="row mb-0">
                        <dt class="col-7 fw-normal text-muted">{{ __('front_desk.dashboard.visitors_inside') }}</dt><dd class="col-5 text-end">{{ $snapshot['visitors_inside'] }}</dd>
                        <dt class="col-7 fw-normal text-muted">{{ __('front_desk.dashboard.pending_callbacks') }}</dt><dd class="col-5 text-end">{{ $snapshot['pending_callbacks'] }}</dd>
                        <dt class="col-7 fw-normal text-muted">{{ __('front_desk.dashboard.pending_couriers') }}</dt><dd class="col-5 text-end">{{ $snapshot['pending_couriers'] }}</dd>
                        <dt class="col-7 fw-normal text-muted">{{ __('front_desk.dashboard.open_incidents') }}</dt><dd class="col-5 text-end">{{ $snapshot['open_incidents'] }}</dd>
                        <dt class="col-7 fw-normal text-muted">{{ __('front_desk.dashboard.unclaimed_lost_found') }}</dt><dd class="col-5 text-end">{{ $snapshot['unclaimed_lost_found'] }}</dd>
                    </dl>
                </div>
            </div>
            @endisset
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="ti ti-device-floppy me-1"></i>{{ __('front_desk.actions.save') }}</button>
                <a href="{{ $isEdit ? route('admin.front-desk.handovers.show', $log) : route('admin.front-desk.handovers.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.cancel') }}</a>
            </div>
        </div>
    </div>
</form>
