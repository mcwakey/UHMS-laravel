@php
    $log = $log ?? null;
    $isEdit = $log !== null;
@endphp

@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ $isEdit ? route('admin.front-desk.couriers.update', $log) : route('admin.front-desk.couriers.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.couriers.section_item') }}</h5></div>
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
                            <label class="form-label">{{ __('front_desk.fields.courier_type') }} <span class="text-danger">*</span></label>
                            <select name="courier_type" class="form-select" required>
                                @foreach($types as $t)
                                <option value="{{ $t->value }}" @selected(old('courier_type', $log?->courier_type?->value) === $t->value)>{{ $t->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('front_desk.fields.status') }}</label>
                            <select name="status" class="form-select" @if($isEdit) required @endif>
                                @unless($isEdit)<option value="">{{ __('front_desk.none') }}</option>@endunless
                                @foreach($statuses as $s)
                                <option value="{{ $s->value }}" @selected(old('status', $log?->status?->value) === $s->value)>{{ $s->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.sender_name') }}</label>
                            <input type="text" name="sender_name" class="form-control" value="{{ old('sender_name', $log?->sender_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.sender_organization') }}</label>
                            <input type="text" name="sender_organization" class="form-control" value="{{ old('sender_organization', $log?->sender_organization) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.recipient_name') }}</label>
                            <input type="text" name="recipient_name" class="form-control" value="{{ old('recipient_name', $log?->recipient_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.recipient_department') }}</label>
                            <select name="recipient_department_id" class="form-select">
                                <option value="">{{ __('front_desk.none') }}</option>
                                @foreach($departments as $d)
                                <option value="{{ $d->id }}" @selected((int) old('recipient_department_id', $log?->recipient_department_id) === $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.courier_company') }}</label>
                            <input type="text" name="courier_company" class="form-control" value="{{ old('courier_company', $log?->courier_company) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.messenger_name') }}</label>
                            <input type="text" name="messenger_name" class="form-control" value="{{ old('messenger_name', $log?->messenger_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.tracking_number') }}</label>
                            <input type="text" name="tracking_number" class="form-control" value="{{ old('tracking_number', $log?->tracking_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('front_desk.fields.reference_number') }}</label>
                            <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number', $log?->reference_number) }}">
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
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.couriers.section_delivery') }}</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('front_desk.fields.received_or_sent_at') }}</label>
                        <input type="datetime-local" name="received_or_sent_at" class="form-control" value="{{ old('received_or_sent_at', optional($log?->received_or_sent_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('front_desk.fields.delivered_to') }}</label>
                        <input type="text" name="delivered_to" class="form-control" value="{{ old('delivered_to', $log?->delivered_to) }}">
                    </div>
                    @if($isEdit)
                    <div class="mb-3">
                        <label class="form-label">{{ __('front_desk.fields.delivered_at') }}</label>
                        <input type="datetime-local" name="delivered_at" class="form-control" value="{{ old('delivered_at', optional($log?->delivered_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                    @endif
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="signature_required" value="0">
                        <input type="checkbox" name="signature_required" value="1" class="form-check-input" id="signature_required" @checked(old('signature_required', $log?->signature_required))>
                        <label class="form-check-label" for="signature_required">{{ __('front_desk.fields.signature_required') }}</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('front_desk.fields.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $log?->notes) }}</textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="ti ti-device-floppy me-1"></i>{{ __('front_desk.actions.save') }}</button>
                        <a href="{{ $isEdit ? route('admin.front-desk.couriers.show', $log) : route('admin.front-desk.couriers.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
