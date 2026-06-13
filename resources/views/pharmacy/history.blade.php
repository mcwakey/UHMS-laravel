@extends('layouts.app')
@section('title', __('pharmacy.dispensing_history'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-history me-2"></i>{{ __('pharmacy.dispensing_history') }}</h4>
    </div>
    <div>
        <a href="{{ route('admin.pharmacy.dispensing.index') }}" class="btn btn-outline-primary btn-md">
            <i class="ti ti-arrow-left me-1"></i>{{ __('pharmacy.back_to_queue') }}
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="{{ __('pharmacy.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="{{ __('common.from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="{{ __('common.to') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>{{ __('common.filter') }}</button>
                <a href="{{ route('admin.pharmacy.history') }}" class="btn btn-outline-secondary btn-md">{{ __('common.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<!-- History Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('pharmacy.rx_number_short') }}</th>
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('common.drug') }}</th>
                        <th>{{ __('pharmacy.batch') }}</th>
                        <th>{{ __('pharmacy.col_qty_dispensed') }}</th>
                        <th>{{ __('pharmacy.dispensed_by') }}</th>
                        <th>{{ __('pharmacy.dispensed_at') }}</th>
                        <th>{{ __('common.notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                    <tr>
                        <td>
                            <a href="{{ route('admin.pharmacy.dispensing.show', $record->prescription_id) }}" class="fw-medium text-primary">
                                {{ $record->prescription->prescription_number ?? '-' }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $record->patient->full_name ?? '-' }}</div>
                            <small class="text-muted">{{ $record->patient->patient_number ?? '' }}</small>
                        </td>
                        <td>
                            @php $drug = $record->prescriptionItem?->drug; @endphp
                            @if($drug)
                                <span class="fw-medium">{{ $drug->name }}</span>
                                <br><small class="text-muted">{{ $drug->dosage_form }} {{ $drug->strength }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><code>—</code></td>
                        <td><span class="badge bg-primary">{{ $record->quantity_dispensed }}</span></td>
                        <td>{{ $record->dispensedBy->name ?? '-' }}</td>
                        <td>
                            <small>{{ $record->dispensed_at?->format('d M Y') }}</small><br>
                            <small class="text-muted">{{ $record->dispensed_at?->format('H:i') }}</small>
                        </td>
                        <td>
                            @if($record->notes)
                                <small>{{ Str::limit($record->notes, 40) }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="ti ti-history fs-1 d-block mb-2"></i>
                            {{ __('pharmacy.no_dispensing_records_found') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($records->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $records->links() }}
</div>
@endif
@endsection
