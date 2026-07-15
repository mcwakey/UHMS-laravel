@php $log = $log ?? null; $isEdit = $log !== null; @endphp
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $isEdit ? $workspaceRoutes->route('admin.front-desk.lost-found.update', $log) : $workspaceRoutes->route('admin.front-desk.lost-found.store') }}">
    @csrf @if($isEdit) @method('PUT') @endif
    <div class="card">
        <div class="card-header"><h5 class="mb-0 fs-15">{{ __('front_desk.lost_found.section_item') }}</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('front_desk.fields.item_category') }} <span class="text-danger">*</span></label>
                    <select name="item_category" class="form-select" required>
                        @foreach($categories as $c)<option value="{{ $c->value }}" @selected(old('item_category', $log?->item_category?->value) === $c->value)>{{ $c->translatedLabel() }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('front_desk.fields.item_status') }}</label>
                    <select name="item_status" class="form-select">
                        @foreach($statuses as $s)<option value="{{ $s->value }}" @selected(old('item_status', $log?->item_status?->value ?? 'found') === $s->value)>{{ $s->translatedLabel() }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('front_desk.fields.found_or_reported_at') }}</label>
                    <input type="datetime-local" name="found_or_reported_at" class="form-control" value="{{ old('found_or_reported_at', optional($log?->found_or_reported_at)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('front_desk.fields.item_description') }} <span class="text-danger">*</span></label>
                    <textarea name="item_description" class="form-control" rows="2" required>{{ old('item_description', $log?->item_description) }}</textarea>
                </div>
                <div class="col-md-4"><label class="form-label">{{ __('front_desk.fields.found_location') }}</label><input type="text" name="found_location" class="form-control" value="{{ old('found_location', $log?->found_location) }}"></div>
                <div class="col-md-4"><label class="form-label">{{ __('front_desk.fields.stored_location') }}</label><input type="text" name="stored_location" class="form-control" value="{{ old('stored_location', $log?->stored_location) }}"></div>
                <div class="col-md-4"><label class="form-label">{{ __('front_desk.fields.found_by_name') }}</label><input type="text" name="found_by_name" class="form-control" value="{{ old('found_by_name', $log?->found_by_name) }}"></div>
                <div class="col-md-4"><label class="form-label">{{ __('front_desk.fields.reported_by_name') }}</label><input type="text" name="reported_by_name" class="form-control" value="{{ old('reported_by_name', $log?->reported_by_name) }}"></div>
                <div class="col-md-4"><label class="form-label">{{ __('front_desk.fields.reported_by_phone') }}</label><input type="text" name="reported_by_phone" class="form-control" value="{{ old('reported_by_phone', $log?->reported_by_phone) }}"></div>
                <div class="col-12"><label class="form-label">{{ __('front_desk.fields.notes') }}</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $log?->notes) }}</textarea></div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('front_desk.actions.save') }}</button>
                <a href="{{ $isEdit ? $workspaceRoutes->route('admin.front-desk.lost-found.show', $log) : $workspaceRoutes->route('admin.front-desk.lost-found.index') }}" class="btn btn-outline-secondary">{{ __('front_desk.actions.cancel') }}</a>
            </div>
        </div>
    </div>
</form>
