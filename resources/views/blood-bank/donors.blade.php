@extends('layouts.app')
@section('title', 'Blood Donors')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div><h4 class="fw-bold mb-1">Blood Donors</h4><p class="text-muted mb-0">Register and search blood donors.</p></div>
    <a href="{{ route('admin.blood-bank.dashboard') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
</div>

<div class="card mb-3">
    <div class="card-header bg-white"><h5 class="card-title mb-0">Register Donor</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.blood-bank.donors.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><label class="form-label">First Name</label><input name="first_name" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Last Name</label><input name="last_name" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select"><option value="">Unknown</option>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Gender</label><input name="gender" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Address</label><input name="address" class="form-control"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Save Donor</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label">Search</label><input name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Donor number, name, phone"></div>
            <div class="col-md-2"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select"><option value="">All</option>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}" @selected(($filters['blood_group'] ?? '') === $g)>{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Screening</label><select name="screening_status" class="form-select"><option value="">All</option>@foreach(['REGISTERED','QUESTIONNAIRE_PENDING','PHYSICAL_ASSESSMENT_PENDING','ELIGIBLE','TEMPORARILY_DEFERRED','PERMANENTLY_DEFERRED'] as $s)<option value="{{ $s }}" @selected(($filters['screening_status'] ?? '') === $s)>{{ str_replace('_',' ',$s) }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
            <div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="{{ route('admin.blood-bank.donors.index') }}">Clear</a></div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>Donor #</th><th>Name</th><th>Group</th><th>Screening Status</th><th>Last Donation</th><th class="text-end">Screening</th></tr></thead>
                <tbody>
                    @forelse($donors as $donor)
                        @php($ss = $donor->screening_status ?? 'REGISTERED')
                        <tr>
                            <td>{{ $donor->donor_number }}</td>
                            <td>{{ $donor->full_name }}<div class="small text-muted">{{ $donor->phone ?? '' }}</div></td>
                            <td>{{ $donor->blood_group ?? 'Unknown' }}</td>
                            <td><span class="badge bg-{{ $ss === 'ELIGIBLE' ? 'success' : ($ss === 'PERMANENTLY_DEFERRED' ? 'danger' : (str_contains($ss,'DEFERRED') ? 'warning text-dark' : 'secondary')) }}">{{ str_replace('_',' ',$ss) }}</span>
                                @if($donor->latestScreening?->deferral_reason)<div class="small text-muted">{{ $donor->latestScreening->deferral_reason }}</div>@endif
                            </td>
                            <td>{{ $donor->last_donation_at?->format('d M Y') ?? '—' }}</td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#scr{{ $donor->id }}">Screen</button></td>
                        </tr>
                        <tr class="collapse" id="scr{{ $donor->id }}">
                            <td colspan="6" class="bg-light">
                                <div class="row g-3 py-2">
                                    {{-- 1. Questionnaire --}}
                                    <div class="col-lg-5">
                                        <div class="small fw-semibold mb-1">1 · Questionnaire &amp; Consent</div>
                                        <form method="POST" action="{{ route('admin.blood-bank.donors.screening.questionnaire', $donor) }}">
                                            @csrf
                                            <div class="row row-cols-2 g-1 small">
                                                @foreach(array_merge($questionnaireRisk['temporary'] ?? [], $questionnaireRisk['permanent'] ?? []) as $key)
                                                    <div class="col"><div class="form-check"><input class="form-check-input" type="checkbox" name="questionnaire[{{ $key }}]" value="1" id="q{{ $donor->id }}{{ $key }}"><label class="form-check-label" for="q{{ $donor->id }}{{ $key }}">{{ ucwords(str_replace('_',' ',$key)) }}</label></div></div>
                                                @endforeach
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mt-2 small">
                                                <div class="form-check"><input class="form-check-input" type="checkbox" name="consent_donate" value="1" checked id="cd{{ $donor->id }}"><label class="form-check-label" for="cd{{ $donor->id }}">Consent to donate</label></div>
                                                <div class="form-check"><input class="form-check-input" type="checkbox" name="consent_testing" value="1" checked id="ct{{ $donor->id }}"><label class="form-check-label" for="ct{{ $donor->id }}">Consent to test</label></div>
                                                <div class="form-check"><input class="form-check-input" type="checkbox" name="consent_contact" value="1" id="cc{{ $donor->id }}"><label class="form-check-label" for="cc{{ $donor->id }}">Consent to contact</label></div>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary mt-2">Save Questionnaire</button>
                                        </form>
                                    </div>
                                    {{-- 2. Physical assessment --}}
                                    <div class="col-lg-4">
                                        <div class="small fw-semibold mb-1">2 · Physical Assessment</div>
                                        <form method="POST" action="{{ route('admin.blood-bank.donors.screening.assessment', $donor) }}" class="row g-1">
                                            @csrf
                                            <div class="col-6"><input name="weight_kg" type="number" step="0.1" class="form-control form-control-sm" placeholder="Weight kg"></div>
                                            <div class="col-6"><input name="temperature_c" type="number" step="0.1" class="form-control form-control-sm" placeholder="Temp °C"></div>
                                            <div class="col-6"><input name="hemoglobin" type="number" step="0.1" class="form-control form-control-sm" placeholder="Hb g/dL"></div>
                                            <div class="col-6"><input name="pulse" type="number" class="form-control form-control-sm" placeholder="Pulse"></div>
                                            <div class="col-6"><input name="bp_systolic" type="number" class="form-control form-control-sm" placeholder="Systolic"></div>
                                            <div class="col-6"><input name="bp_diastolic" type="number" class="form-control form-control-sm" placeholder="Diastolic"></div>
                                            <div class="col-12"><input name="fitness_notes" class="form-control form-control-sm" placeholder="Fitness notes"></div>
                                            <div class="col-12"><button class="btn btn-sm btn-outline-primary">Save Assessment</button></div>
                                        </form>
                                    </div>
                                    {{-- 3. Eligibility --}}
                                    <div class="col-lg-3">
                                        <div class="small fw-semibold mb-1">3 · Eligibility Decision</div>
                                        <form method="POST" action="{{ route('admin.blood-bank.donors.screening.eligibility', $donor) }}" class="row g-1">
                                            @csrf
                                            <div class="col-12"><select name="decision" class="form-select form-select-sm"><option value="">Auto-suggest</option><option value="ELIGIBLE">Eligible</option><option value="TEMPORARILY_DEFERRED">Temporarily defer</option><option value="PERMANENTLY_DEFERRED">Permanently defer</option></select></div>
                                            <div class="col-12"><input name="deferral_reason" class="form-control form-control-sm" placeholder="Deferral reason"></div>
                                            <div class="col-12"><input name="deferral_until" type="date" class="form-control form-control-sm" placeholder="Defer until"></div>
                                            @can('blood_bank.donor.override_eligibility')
                                            <div class="col-12"><div class="form-check small"><input class="form-check-input" type="checkbox" name="override" value="1" id="ov{{ $donor->id }}"><label class="form-check-label" for="ov{{ $donor->id }}">Override</label></div></div>
                                            <div class="col-12"><input name="override_reason" class="form-control form-control-sm" placeholder="Override reason"></div>
                                            @endcan
                                            <div class="col-12"><button class="btn btn-sm btn-success">Decide</button></div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No donors found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($donors->hasPages())<div class="card-footer">{{ $donors->links() }}</div>@endif
</div>
@endsection
