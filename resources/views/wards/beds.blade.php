@extends('layouts.app')
@section('title', __('wards.bed_management'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('wards.bed_management') }} <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">{{ __('wards.total') }}: {{ $beds->total() }}</span></h4>
    </div>
    <div class="text-end d-flex gap-2">
        @can('beds.manage')
        <button type="button" class="btn btn-primary btn-md fs-13" data-bs-toggle="modal" data-bs-target="#addBedModal"><i class="ti ti-plus me-1"></i>{{ __('wards.new_bed') }}</button>
        @endcan
        <a href="{{ $workspaceRoutes->route('admin.wards.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-building-hospital me-1"></i>{{ __('wards.wards') }}</a>
        <a href="{{ $workspaceRoutes->route('admin.wards.bed-map') }}" class="btn btn-outline-success btn-md fs-13"><i class="ti ti-map me-1"></i>{{ __('wards.bed_map') }}</a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ $workspaceRoutes->route('admin.wards.beds') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <select name="ward_id" class="form-select">
                    <option value="">{{ __('wards.all_wards') }}</option>
                    @foreach($wards as $ward)
                        <option value="{{ $ward->id }}" {{ request('ward_id') == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">{{ __('common.all_statuses') }}</option>
                    @foreach(\App\Enums\BedStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="bed_type" class="form-select">
                    <option value="">{{ __('wards.all_types') }}</option>
                    @foreach(\App\Enums\BedType::cases() as $type)
                        <option value="{{ $type->value }}" {{ request('bed_type') == $type->value ? 'selected' : '' }}>{{ $type->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-outline-primary btn-md"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                <a href="{{ $workspaceRoutes->route('admin.wards.beds') }}" class="btn btn-outline-secondary btn-md ms-1"><i class="ti ti-x me-1"></i>{{ __('common.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<!-- Beds Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('wards.bed_number') }}</th>
                        <th>{{ __('wards.ward') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('wards.daily_rate') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('wards.current_patient') }}</th>
                        <th>{{ __('common.notes') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($beds as $bed)
                    <tr>
                        <td><span class="fw-medium">{{ $bed->bed_number }}</span></td>
                        <td>{{ $bed->ward->name }}</td>
                        <td>{{ $bed->bed_type->translatedLabel() }}</td>
                        <td>GH₵ {{ number_format($bed->daily_rate, 2) }}</td>
                        <td><span class="badge badge-soft-{{ $bed->status->color() }}">{{ $bed->status->translatedLabel() }}</span></td>
                        <td>
                            @if($bed->currentAdmission)
                                <a href="{{ $workspaceRoutes->route('admin.admissions.show', $bed->currentAdmission) }}" class="text-decoration-none">
                                    {{ $bed->currentAdmission->patient->full_name }}
                                </a>
                            @elseif($bed->activeReservation)
                                <a href="{{ $workspaceRoutes->route('admin.admissions.requests.show', $bed->activeReservation->admissionRequest) }}" class="text-decoration-none">
                                    {{ $bed->activeReservation->admissionRequest?->patient?->full_name ?? __('statuses.default.reserved') }}
                                </a>
                                <div><small class="text-muted">{{ __('admissions.bed_reservation_statuses.active') }}</small></div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            {{ Str::limit($bed->notes, 30) ?? '—' }}
                            @if($bed->status_reason)
                                <div><small class="text-muted">{{ Str::limit($bed->status_reason, 45) }}</small></div>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('beds.manage')
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editBedModal{{ $bed->id }}" aria-label="{{ __('common.edit') }}" title="{{ __('common.edit') }}">
                                <i class="ti ti-pencil"></i>
                            </button>
                            @endcan
                            @can('beds.status.manage')
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#bedStatusModal{{ $bed->id }}" aria-label="{{ __('common.status') }}" title="{{ __('common.status') }}">
                                <i class="ti ti-adjustments"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="ti ti-bed fs-1 d-block mb-2"></i>
                            {{ __('wards.no_beds_found') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($beds->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $beds->withQueryString()->links() }}
</div>
@endif

<!-- Add Bed Modal -->
@can('beds.manage')
<div class="modal fade" id="addBedModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.wards.beds.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('wards.add_new_bed') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('wards.ward') }} <span class="text-danger">*</span></label>
                        <select name="ward_id" class="form-select" required>
                            <option value="">{{ __('wards.select_ward') }}</option>
                            @foreach($wards as $ward)
                                <option value="{{ $ward->id }}">{{ $ward->name }} ({{ $ward->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('wards.bed_number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="bed_number" class="form-control" required placeholder="{{ __('wards.bed_number_placeholder') }}">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('wards.bed_type') }} <span class="text-danger">*</span></label>
                            <select name="bed_type" class="form-select" required>
                                @foreach(\App\Enums\BedType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('wards.daily_rate_cedis') }} <span class="text-danger">*</span></label>
                            <input type="number" name="daily_rate" class="form-control" required min="0" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('wards.create_bed') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

<!-- Edit Bed Modals -->
@can('beds.manage')
@foreach($beds as $bed)
<div class="modal fade" id="editBedModal{{ $bed->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.wards.beds.update', $bed) }}">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('wards.edit_bed_title', ['number' => $bed->bed_number]) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('wards.bed_number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="bed_number" class="form-control" required value="{{ $bed->bed_number }}">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('wards.bed_type') }}</label>
                            <select name="bed_type" class="form-select">
                                @foreach(\App\Enums\BedType::cases() as $type)
                                    <option value="{{ $type->value }}" {{ $bed->bed_type == $type ? 'selected' : '' }}>{{ $type->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select name="status" class="form-select">
                                @foreach(\App\Enums\BedStatus::cases() as $status)
                                    <option value="{{ $status->value }}" {{ $bed->status == $status ? 'selected' : '' }}>{{ $status->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('wards.daily_rate_cedis') }}</label>
                        <input type="number" name="daily_rate" class="form-control" min="0" step="0.01" value="{{ $bed->daily_rate }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2">{{ $bed->notes }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('admissions.reason') }}</label>
                        <textarea name="status_reason" class="form-control" rows="2">{{ $bed->status_reason }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('wards.update_bed') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach
@endcan

@can('beds.status.manage')
@foreach($beds as $bed)
<div class="modal fade" id="bedStatusModal{{ $bed->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.wards.beds.status', $bed) }}">
            @csrf @method('PATCH')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('common.status') }} — {{ $bed->ward->name }} / {{ $bed->bed_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.status') }}</label>
                        <select name="status" class="form-select" required>
                            @foreach(\App\Enums\BedStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected($bed->status === $status)>{{ $status->translatedLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('admissions.reason') }}</label>
                        <textarea name="reason" class="form-control" rows="3">{{ $bed->status_reason }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('wards.update_bed') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach
@endcan
@endsection
