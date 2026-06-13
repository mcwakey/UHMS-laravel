@extends('layouts.app')
@section('title', __('prescriptions.prescriptions'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('prescriptions.prescriptions') }}</h4>
        <small class="text-muted">{{ __('prescriptions.manage_subtitle') }}</small>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">{{ __('common.search') }}</label>
                <input type="text" name="search" class="form-control" placeholder="{{ __('prescriptions.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('common.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('common.all_statuses') }}</option>
                    @foreach(\App\Enums\PrescriptionStatus::cases() as $status)
                    <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-search me-1"></i>{{ __('common.filter') }}</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.prescriptions.index') }}" class="btn btn-outline-secondary w-100">{{ __('common.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<!-- Prescriptions Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('pharmacy.rx_number_short') }}</th>
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('common.doctor') }}</th>
                        <th>{{ __('pharmacy.items') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.date') }}</th>
                        <th>{{ __('common.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prescriptions as $prescription)
                    <tr>
                        <td><span class="fw-medium">{{ $prescription->prescription_number }}</span></td>
                        <td>
                            <div class="fw-medium">{{ $prescription->patient->full_name }}</div>
                            <small class="text-muted">{{ $prescription->patient->patient_number }}</small>
                        </td>
                        <td>Dr. {{ $prescription->doctor->full_name }}</td>
                        <td><span class="badge bg-secondary">{{ __('prescriptions.items_count', ['count' => $prescription->items->count()]) }}</span></td>
                        <td><x-status-badge :status="$prescription->status" /></td>
                        <td><small>{{ $prescription->created_at->format('d M Y, h:i A') }}</small></td>
                        <td>
                            <a aria-label="{{ __('common.view') }}" title="{{ __('common.view') }}" href="{{ route('admin.prescriptions.show', $prescription) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="ti ti-prescription fs-1 d-block mb-2"></i>
                            {{ __('prescriptions.no_prescriptions_found') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-center mt-3">
    {{ $prescriptions->withQueryString()->links() }}
</div>
@endsection
