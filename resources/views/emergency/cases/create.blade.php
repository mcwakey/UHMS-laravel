@extends('layouts.app')
@section('title', 'New Emergency Case')

@section('content')
<div class="container py-3">
    <h1 class="h3 mb-3"><i class="ti ti-ambulance text-danger"></i> Register Emergency Case</h1>

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.emergency.cases.store') }}" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                    @if($patient)
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                        <input type="text" class="form-control" value="{{ $patient->full_name }} ({{ $patient->patient_number }})" disabled>
                    @else
                        <select name="patient_id" class="form-select" required>
                            <option value="">— Select patient —</option>
                            @foreach(\App\Models\Patient::orderBy('first_name')->limit(500)->get() as $p)
                                <option value="{{ $p->id }}" @selected(old('patient_id')==$p->id)>{{ $p->full_name }} ({{ $p->patient_number }})</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="form-label">Arrival Mode</label>
                    <select name="arrival_mode" class="form-select">
                        @foreach($arrivalModes as $m)
                            <option value="{{ $m->value }}" @selected(old('arrival_mode', 'walk_in')==$m->value)>{{ $m->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Arrival Time</label>
                    <input type="datetime-local" name="arrival_time" value="{{ old('arrival_time', now()->format('Y-m-d\TH:i')) }}" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Brought By</label>
                    <input type="text" name="brought_by" value="{{ old('brought_by') }}" class="form-control" placeholder="e.g. Ambulance crew, family, police">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Accompanied By</label>
                    <input type="text" name="accompanied_by" value="{{ old('accompanied_by') }}" class="form-control" placeholder="Relative / NOK">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Referral Source</label>
                    <input type="text" name="referral_source" value="{{ old('referral_source') }}" class="form-control" placeholder="Referring facility / doctor">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Initial Triage (optional)</label>
                    <select name="triage_category" class="form-select">
                        <option value="">— Not yet triaged —</option>
                        @foreach($triages as $t)
                            <option value="{{ $t->value }}" @selected(old('triage_category')==$t->value)>{{ $t->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Chief Complaint</label>
                    <textarea name="chief_complaint" rows="3" class="form-control">{{ old('chief_complaint') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('admin.emergency.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-danger"><i class="ti ti-device-floppy"></i> Register Case</button>
        </div>
    </form>
</div>
@endsection
