@extends('layouts.app')
@section('title', __('blood_bank.blood_storage_locations'))

@section('content')
<x-page-header :title="__('blood_bank.storage_locations')" description="Manage blood bank refrigerators, freezers, and storage areas." icon="ti-fridge">
    <x-slot:actions>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#storageModal" id="addStorageBtn"><i class="ti ti-plus me-1"></i>{{ __('blood_bank.add_storage') }}</button>
        <a href="{{ route('admin.blood-bank.donations.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>{{ __('blood_bank.donations_link') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>{{ __('blood_bank.name') }}</th><th>{{ __('blood_bank.code') }}</th><th>{{ __('blood_bank.type') }}</th><th>{{ __('blood_bank.temp_range') }}</th><th class="text-center">{{ __('blood_bank.units') }}</th><th class="text-center">{{ __('blood_bank.active') }}</th><th class="text-end">{{ __('blood_bank.actions') }}</th></tr></thead>
                <tbody>
                    @forelse($locations as $loc)
                        <tr>
                            <td class="fw-semibold">{{ $loc->name }}</td>
                            <td><code>{{ $loc->code }}</code></td>
                            <td>{{ str_replace('_',' ', $loc->location_type) }}</td>
                            <td>
                                @if($loc->temperature_min !== null || $loc->temperature_max !== null)
                                    {{ $loc->temperature_min !== null ? rtrim(rtrim((string)$loc->temperature_min,'0'),'.') : '–' }}…{{ $loc->temperature_max !== null ? rtrim(rtrim((string)$loc->temperature_max,'0'),'.') : '–' }} °C
                                @else <span class="text-muted">—</span> @endif
                            </td>
                            <td class="text-center">{{ $loc->total_units }} <span class="text-muted small">({{ $loc->available_units }} avail)</span></td>
                            <td class="text-center">
                                @if($loc->is_active)<span class="badge bg-success-lt">{{ __('blood_bank.active') }}</span>@else<span class="badge bg-secondary-lt">{{ __('blood_bank.inactive') }}</span>@endif
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary edit-storage"
                                    data-id="{{ $loc->id }}" data-name="{{ $loc->name }}" data-code="{{ $loc->code }}"
                                    data-type="{{ $loc->location_type }}" data-min="{{ $loc->temperature_min }}" data-max="{{ $loc->temperature_max }}"
                                    data-active="{{ $loc->is_active ? 1 : 0 }}" data-notes="{{ $loc->notes }}"
                                    data-action="{{ route('admin.blood-bank.storage.update', $loc) }}"><i class="ti ti-edit"></i></button>
                                <form method="POST" action="{{ route('admin.blood-bank.storage.toggle', $loc) }}" class="d-inline">@csrf @method('PATCH')
                                    <button class="btn btn-sm {{ $loc->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">{{ $loc->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state icon="ti-fridge-off" :title="__('blood_bank.no_storage_locations')" :message="__('blood_bank.no_storage_locations_message')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add / Edit modal --}}
<div class="modal fade" id="storageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.blood-bank.storage.store') }}" id="storageForm">
                @csrf
                <input type="hidden" name="_method" value="POST" id="storageMethod">
                <div class="modal-header">
                    <h5 class="modal-title" id="storageModalTitle"><i class="ti ti-fridge me-2 text-primary"></i>{{ __('blood_bank.add_storage_location') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-7"><label class="form-label">{{ __('blood_bank.name') }} <span class="text-danger">*</span></label><input name="name" id="st_name" class="form-control" required></div>
                        <div class="col-md-5"><label class="form-label">{{ __('blood_bank.code') }} <span class="text-danger">*</span></label><input name="code" id="st_code" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.type') }}</label>
                            <select name="location_type" id="st_type" class="form-select">
                                @foreach($locationTypes as $t)<option value="{{ $t }}">{{ str_replace('_',' ',$t) }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Min °C</label><input name="temperature_min" id="st_min" type="number" step="0.1" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Max °C</label><input name="temperature_max" id="st_max" type="number" step="0.1" class="form-control"></div>
                        <div class="col-12"><label class="form-label">{{ __('blood_bank.notes') }}</label><textarea name="notes" id="st_notes" class="form-control" rows="2"></textarea></div>
                        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="st_active" checked><label class="form-check-label" for="st_active">{{ __('blood_bank.active') }}</label></div></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('blood_bank.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('storageForm');
    var method = document.getElementById('storageMethod');
    var title = document.getElementById('storageModalTitle');
    var storeAction = '{{ route('admin.blood-bank.storage.store') }}';
    var modalEl = document.getElementById('storageModal');

    function resetForm() {
        form.reset();
        form.action = storeAction;
        method.value = 'POST';
        title.innerHTML = @json('<i class="ti ti-fridge me-2 text-primary"></i>' . __('blood_bank.add_storage_location'));
        document.getElementById('st_active').checked = true;
    }

    document.getElementById('addStorageBtn').addEventListener('click', resetForm);

    document.querySelectorAll('.edit-storage').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.action = btn.dataset.action;
            method.value = 'PUT';
            title.innerHTML = @json('<i class="ti ti-edit me-2 text-primary"></i>' . __('blood_bank.edit_storage_location'));
            document.getElementById('st_name').value = btn.dataset.name || '';
            document.getElementById('st_code').value = btn.dataset.code || '';
            document.getElementById('st_type').value = btn.dataset.type || '';
            document.getElementById('st_min').value = btn.dataset.min || '';
            document.getElementById('st_max').value = btn.dataset.max || '';
            document.getElementById('st_notes').value = btn.dataset.notes || '';
            document.getElementById('st_active').checked = btn.dataset.active === '1';
            if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });
});
</script>
@endpush
@endsection
