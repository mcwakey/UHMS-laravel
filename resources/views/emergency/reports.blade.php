@extends('layouts.app')
@section('title', __('emergency.reports_title'))

@section('content')
<x-page-header :title="__('emergency.reports_title')" :description="__('emergency.reports_description')" icon="ti-report-analytics">
    <x-slot:actions>
        <a href="{{ route('admin.emergency.board') }}" class="btn btn-outline-secondary btn-sm">{{ __('emergency.emergency_board_btn') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.emergency.reports.index') }}">
            <div class="col-md-3">
                <label class="form-label">{{ __('emergency.date_from') }}</label>
                <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('emergency.date_to') }}</label>
                <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('emergency.triage') }}</label>
                <select class="form-select" name="triage_category">
                    <option value="">{{ __('emergency.all_triage') }}</option>
                    @foreach(['RED','ORANGE','YELLOW','GREEN','BLACK'] as $category)
                        <option value="{{ $category }}" @selected(($filters['triage_category'] ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('emergency.disposition') }}</label>
                <select class="form-select" name="disposition">
                    <option value="">{{ __('emergency.all_dispositions') }}</option>
                    @foreach(['ADMITTED','DISCHARGED','TRANSFERRED_TO_OPD','TRANSFERRED_TO_THEATRE','REFERRED_OUT','LEFT_AGAINST_MEDICAL_ADVICE','ABSCONDED','DIED','DEAD_ON_ARRIVAL'] as $disposition)
                        <option value="{{ $disposition }}" @selected(($filters['disposition'] ?? '') === $disposition)>{{ str_replace('_', ' ', $disposition) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">{{ __('emergency.run_report') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    @forelse($dispositionCounts as $disposition => $total)
        <div class="col-6 col-xl-2">
            <div class="card border-0 bg-light">
                <div class="card-body py-3">
                    <div class="small text-muted">{{ str_replace('_', ' ', $disposition ?: __('emergency.open_label')) }}</div>
                    <div class="h4 mb-0">{{ $total }}</div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-light mb-0">{{ __('emergency.no_disposition_records') }}</div>
        </div>
    @endforelse
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('emergency.attendance') }}</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('emergency.col_emergency_number') }}</th>
                        <th>{{ __('emergency.patient') }}</th>
                        <th>{{ __('emergency.col_arrival') }}</th>
                        <th>{{ __('emergency.triage') }}</th>
                        <th>{{ __('emergency.status') }}</th>
                        <th>{{ __('emergency.col_disposition') }}</th>
                        <th>{{ __('emergency.col_team') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cases as $case)
                        <tr>
                            <td><a href="{{ route('admin.emergency.cases.show', $case) }}">{{ $case->emergency_number }}</a></td>
                            <td>
                                <div class="fw-semibold">{{ $case->patient->full_name ?? __('emergency.unknown_patient_row') }}</div>
                                <small class="text-muted">{{ $case->patient->patient_number ?? '' }}</small>
                            </td>
                            <td>{{ $case->arrival_time?->format('d M Y H:i') }}</td>
                            <td><span class="badge {{ $case->triage_badge_class }}">{{ $case->triage_category ?? 'UNTRIAGED' }}</span></td>
                            <td>{{ str_replace('_', ' ', $case->emergency_status) }}</td>
                            <td>{{ $case->disposition ? str_replace('_', ' ', $case->disposition) : __('emergency.open_label') }}</td>
                            <td>
                                <div class="small">{{ __('emergency.dr_short') }}: {{ $case->assignedDoctor->name ?? __('emergency.unassigned') }}</div>
                                <div class="small text-muted">{{ __('emergency.nurse_short') }}: {{ $case->assignedNurse->name ?? __('emergency.unassigned') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state icon="ti-report-off" :message="__('emergency.no_report_cases')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($cases->hasPages())
        <div class="card-footer">{{ $cases->links() }}</div>
    @endif
</div>
@endsection
