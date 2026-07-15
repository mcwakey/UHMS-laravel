@extends('layouts.app')
@section('title', __('nursing.consultations.details'))
@section('content')
<x-page-header :title="__('nursing.consultations.details').' · '.$visit->visit_number" :description="$visit->patient?->patient_number" icon="ti-stethoscope" />

<div class="d-flex flex-wrap gap-2 mb-3"><a href="{{ route('nursing.consultations.index') }}" class="btn btn-outline-secondary">{{ __('common.back') }}</a><a href="{{ route('nursing.opd.show', $visit) }}" class="btn btn-primary">{{ __('nursing.case.title') }}</a></div>

<div class="row g-3">
    <div class="col-xl-7"><div class="card border h-100"><div class="card-header"><h5 class="mb-0">{{ __('nursing.consultations.sessions') }}</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>{{ __('nursing.common.department') }}</th><th>{{ __('nursing.consultations.clinician') }}</th><th>{{ __('nursing.common.status') }}</th><th>{{ __('nursing.common.recorded_at') }}</th></tr></thead><tbody>
        @forelse($visit->consultationRoutes->sortByDesc('id') as $session)<tr><td>{{ $session->department?->name }}</td><td>{{ $session->doctor?->name ?? __('nursing.common.not_assigned') }}</td><td><span class="badge bg-light text-dark">{{ $session->status }}</span></td><td>{{ ($session->started_at ?? $session->created_at)?->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">{{ __('nursing.consultations.empty') }}</td></tr>@endforelse
    </tbody></table></div></div></div></div>
    <div class="col-xl-5"><div class="card border h-100"><div class="card-header"><h5 class="mb-0">{{ __('nursing.case.vitals') }}</h5></div><div class="card-body">@forelse($visit->vitals->sortByDesc('recorded_at') as $vital)<div class="border-bottom py-2">BP {{ $vital->blood_pressure ?? '—' }} · HR {{ $vital->heart_rate ?? '—' }} · SpO₂ {{ $vital->spo2 ?? '—' }}%<small class="text-muted d-block">{{ $vital->recorded_at?->format('d M Y H:i') }}</small></div>@empty<p class="text-muted mb-0">{{ __('nursing.case.no_data') }}</p>@endforelse</div></div></div>

    @foreach($visit->medicalRecords as $record)
        <div class="col-12"><div class="card border"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">{{ $record->department?->name ?? __('nursing.consultations.record') }}</h5><span class="text-muted small">{{ $record->doctor?->name }}</span></div><div class="card-body">
            @if($record->final_note)<div class="alert alert-light border">{{ $record->final_note }}</div>@endif
            <div class="row g-3"><div class="col-md-4"><h6>{{ __('nursing.consultations.complaints') }}</h6>@forelse($record->complaints as $item)<div class="small border-bottom py-1">{{ $item->complaint ?? $item->description ?? '—' }}</div>@empty<span class="text-muted small">{{ __('nursing.case.no_data') }}</span>@endforelse</div><div class="col-md-4"><h6>{{ __('nursing.consultations.diagnoses') }}</h6>@forelse($record->diagnoses as $item)<div class="small border-bottom py-1">{{ $item->diagnosis ?? $item->description ?? '—' }}</div>@empty<span class="text-muted small">{{ __('nursing.case.no_data') }}</span>@endforelse</div><div class="col-md-4"><h6>{{ __('nursing.case.treatments') }}</h6>@forelse($record->treatments as $item)<div class="small border-bottom py-1">{{ $item->type }} — {{ $item->description }}</div>@empty<span class="text-muted small">{{ __('nursing.case.no_data') }}</span>@endforelse</div></div>
        </div></div></div>
    @endforeach
</div>
@endsection
