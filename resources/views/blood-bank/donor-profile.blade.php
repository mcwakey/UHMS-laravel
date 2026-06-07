@extends('layouts.app')
@section('title', 'Donor · '.$donor->full_name)

@php
    $answers = $screening?->questionnaire ?? [];
    $hasScreening = $screening !== null;
    $temporary = $questionnaireRisk['temporary'] ?? [];
    $permanent = $questionnaireRisk['permanent'] ?? [];
    $ss = $donor->screening_status ?? 'REGISTERED';
@endphp

@section('content')
<x-page-header :title="$donor->full_name" description="Donor profile and WHO screening workspace." icon="ti-clipboard-heart"
    :breadcrumbs="[['label' => 'Blood Donors', 'url' => route('admin.blood-bank.donors.index')], ['label' => $donor->donor_number]]">
    <x-slot:actions>
        <a href="{{ route('admin.blood-bank.donors.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>All Donors</a>
        @if($donor->canDonate())
            <a href="{{ route('admin.blood-bank.donations.index') }}" class="btn btn-success btn-sm"><i class="ti ti-droplet-plus me-1"></i>Record Donation</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    {{-- Donor summary --}}
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="avatar avatar-lg rounded-circle bg-red-lt text-red"><i class="ti ti-user fs-2"></i></span>
                    <div>
                        <h5 class="mb-0">{{ $donor->full_name }}</h5>
                        <div class="small text-muted">{{ $donor->donor_number }}</div>
                        <div class="mt-1"><x-status-badge :status="$ss" domain="donor_screening" /></div>
                    </div>
                </div>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Blood Group</dt><dd class="col-7">@if($donor->blood_group)<span class="badge bg-red-lt">{{ $donor->blood_group }}</span>@else Unknown @endif</dd>
                    <dt class="col-5 text-muted">Age / DOB</dt><dd class="col-7">{{ $donor->age !== null ? $donor->age.' yrs' : '—' }} <span class="text-muted">{{ $donor->date_of_birth?->format('d M Y') }}</span></dd>
                    <dt class="col-5 text-muted">Gender</dt><dd class="col-7">{{ $donor->gender ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Phone</dt><dd class="col-7">{{ $donor->phone ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Email</dt><dd class="col-7 text-truncate">{{ $donor->email ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Address</dt><dd class="col-7">{{ $donor->address ?: '—' }}</dd>
                    <dt class="col-5 text-muted">Last Donation</dt><dd class="col-7">{{ $donor->last_donation_at?->format('d M Y') ?? 'Never' }}</dd>
                    <dt class="col-5 text-muted">Registered By</dt><dd class="col-7">{{ $donor->registeredBy->full_name ?? '—' }}</dd>
                </dl>
                @if($donor->isDeferred() && $donor->deferral_reason)
                    <div class="alert alert-warning mt-3 mb-0 py-2 small"><i class="ti ti-alert-triangle me-1"></i><strong>Deferred:</strong> {{ $donor->deferral_reason }}@if($donor->deferred_until) (until {{ $donor->deferred_until->format('d M Y') }})@endif</div>
                @endif
            </div>
        </div>

        {{-- Suggested eligibility (the "why") --}}
        <div class="card mt-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-bulb me-1 text-warning"></i>System Eligibility Assessment</h6></div>
            <div class="card-body">
                @if(!$hasScreening)
                    <p class="text-muted small mb-0">Start the screening to generate an eligibility assessment.</p>
                @else
                    <div class="mb-2">Suggested decision:
                        <x-status-badge :status="$suggestion['decision'] ?? 'NEEDS_REVIEW'" domain="donor_screening" />
                    </div>
                    @if(!empty($suggestion['flags']))
                        <div class="small fw-semibold text-danger mb-1">Flags ({{ count($suggestion['flags']) }})</div>
                        <ul class="small ps-3 mb-0">
                            @foreach($suggestion['flags'] as $flag)<li>{{ $flag }}</li>@endforeach
                        </ul>
                    @else
                        <p class="small text-success mb-0"><i class="ti ti-circle-check me-1"></i>No deferral flags — meets the configured thresholds.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Screening workspace --}}
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0"><i class="ti ti-list-check me-1"></i>WHO Donor Screening</h6>
                <span class="small text-muted">Stage: <strong>{{ str_replace('_',' ', $screening->stage ?? 'NOT STARTED') }}</strong></span>
            </div>
            <div class="card-body">
                @can('blood_bank.screening.perform')
                <ul class="nav nav-tabs mb-3" id="screenTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabQ" type="button">1 · Questionnaire</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabP" type="button">2 · Physical</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabE" type="button">3 · Eligibility</button></li>
                </ul>
                <div class="tab-content">
                    {{-- 1. Questionnaire --}}
                    <div class="tab-pane fade show active" id="tabQ">
                        <form method="POST" action="{{ route('admin.blood-bank.donors.screening.questionnaire', $donor) }}">
                            @csrf
                            <p class="small text-muted">Tick any that apply. Saved answers are retained below — they reflect what was recorded.</p>
                            <div class="fw-semibold small mb-1">Temporary-risk questions</div>
                            <div class="row row-cols-1 row-cols-md-2 g-1 small mb-3">
                                @foreach($temporary as $key)
                                    <div class="col"><div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="questionnaire[{{ $key }}]" value="1" id="q{{ $key }}" @checked(!empty($answers[$key]))>
                                        <label class="form-check-label" for="q{{ $key }}">{{ ucwords(str_replace('_',' ',$key)) }}</label>
                                    </div></div>
                                @endforeach
                            </div>
                            <div class="fw-semibold small mb-1 text-danger">Permanent-risk questions</div>
                            <div class="row row-cols-1 row-cols-md-2 g-1 small mb-3">
                                @foreach($permanent as $key)
                                    <div class="col"><div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="questionnaire[{{ $key }}]" value="1" id="q{{ $key }}" @checked(!empty($answers[$key]))>
                                        <label class="form-check-label" for="q{{ $key }}">{{ ucwords(str_replace('_',' ',$key)) }}</label>
                                    </div></div>
                                @endforeach
                            </div>
                            <div class="d-flex flex-wrap gap-3 small border-top pt-2">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="consent_donate" value="1" id="cd" @checked($hasScreening ? $screening->consent_donate : true)><label class="form-check-label" for="cd">Consent to donate</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="consent_testing" value="1" id="ct" @checked($hasScreening ? $screening->consent_testing : true)><label class="form-check-label" for="ct">Consent to test</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="consent_contact" value="1" id="cc" @checked($hasScreening ? $screening->consent_contact : false)><label class="form-check-label" for="cc">Consent to contact</label></div>
                            </div>
                            <div class="mt-3"><button class="btn btn-primary btn-sm"><i class="ti ti-device-floppy me-1"></i>Save Questionnaire</button>
                                @if($screening?->questionnaire_at)<span class="small text-muted ms-2">Last saved {{ $screening->questionnaire_at->format('d M Y H:i') }} by {{ $screening->questionnaireBy->full_name ?? '—' }}</span>@endif
                            </div>
                        </form>
                    </div>

                    {{-- 2. Physical assessment --}}
                    <div class="tab-pane fade" id="tabP">
                        <form method="POST" action="{{ route('admin.blood-bank.donors.screening.assessment', $donor) }}" class="row g-2">
                            @csrf
                            <div class="col-md-3"><label class="form-label small">Weight (kg)</label><input name="weight_kg" type="number" step="0.1" class="form-control" value="{{ $screening?->weight_kg }}"></div>
                            <div class="col-md-3"><label class="form-label small">Temp (°C)</label><input name="temperature_c" type="number" step="0.1" class="form-control" value="{{ $screening?->temperature_c }}"></div>
                            <div class="col-md-3"><label class="form-label small">Hb (g/dL)</label><input name="hemoglobin" type="number" step="0.1" class="form-control" value="{{ $screening?->hemoglobin }}"></div>
                            <div class="col-md-3"><label class="form-label small">Pulse</label><input name="pulse" type="number" class="form-control" value="{{ $screening?->pulse }}"></div>
                            <div class="col-md-3"><label class="form-label small">Systolic</label><input name="bp_systolic" type="number" class="form-control" value="{{ $screening?->bp_systolic }}"></div>
                            <div class="col-md-3"><label class="form-label small">Diastolic</label><input name="bp_diastolic" type="number" class="form-control" value="{{ $screening?->bp_diastolic }}"></div>
                            <div class="col-md-6"><label class="form-label small">General Appearance</label><input name="general_appearance" class="form-control" value="{{ $screening?->general_appearance }}"></div>
                            <div class="col-md-6"><label class="form-label small">Venous Access</label><input name="venous_access" class="form-control" value="{{ $screening?->venous_access }}"></div>
                            <div class="col-md-6"><label class="form-label small">Fitness Notes</label><input name="fitness_notes" class="form-control" value="{{ $screening?->fitness_notes }}"></div>
                            <div class="col-12"><button class="btn btn-primary btn-sm"><i class="ti ti-device-floppy me-1"></i>Save Assessment</button>
                                @if($screening?->assessed_at)<span class="small text-muted ms-2">Assessed {{ $screening->assessed_at->format('d M Y H:i') }} by {{ $screening->assessedBy->full_name ?? '—' }}</span>@endif
                            </div>
                        </form>
                    </div>

                    {{-- 3. Eligibility --}}
                    <div class="tab-pane fade" id="tabE">
                        <form method="POST" action="{{ route('admin.blood-bank.donors.screening.eligibility', $donor) }}" class="row g-2">
                            @csrf
                            <div class="col-md-6"><label class="form-label small">Decision</label>
                                <select name="decision" class="form-select">
                                    <option value="">Use system suggestion ({{ str_replace('_',' ', $suggestion['decision'] ?? 'NEEDS REVIEW') }})</option>
                                    @foreach(['ELIGIBLE'=>'Eligible','TEMPORARILY_DEFERRED'=>'Temporarily defer','PERMANENTLY_DEFERRED'=>'Permanently defer'] as $val=>$lbl)
                                        <option value="{{ $val }}" @selected($screening?->eligibility_decision === $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label small">Defer Until (temporary)</label><input name="deferral_until" type="date" class="form-control" value="{{ $screening?->deferral_until?->format('Y-m-d') }}"></div>
                            <div class="col-12"><label class="form-label small">Deferral Reason</label><input name="deferral_reason" class="form-control" value="{{ $screening?->deferral_reason }}" placeholder="Required when deferring"></div>
                            @can('blood_bank.donor.override_eligibility')
                            <div class="col-12"><div class="border rounded p-2 bg-light-subtle">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="override" value="1" id="ov" @checked($screening?->eligibility_overridden)><label class="form-check-label fw-semibold" for="ov">Override system suggestion</label></div>
                                <input name="override_reason" class="form-control form-control-sm mt-2" value="{{ $screening?->override_reason }}" placeholder="Override reason (required to mark a flagged donor eligible)">
                            </div></div>
                            @endcan
                            <div class="col-12"><label class="form-label small">Notes</label><input name="notes" class="form-control" value="{{ $screening?->notes }}"></div>
                            <div class="col-12"><button class="btn btn-success btn-sm"><i class="ti ti-gavel me-1"></i>Record Decision</button>
                                @if($screening?->reviewed_at)<span class="small text-muted ms-2">Decided {{ $screening->reviewed_at->format('d M Y H:i') }} by {{ $screening->reviewedBy->full_name ?? '—' }}</span>@endif
                            </div>
                        </form>
                    </div>
                </div>
                @else
                    <p class="text-muted mb-0">You do not have permission to perform donor screening.</p>
                @endcan
            </div>
        </div>

        {{-- Donation history --}}
        <div class="card mt-3">
            <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-history me-1"></i>Donation History</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light"><tr><th>Donation</th><th>Date</th><th>Group</th><th>Unit</th><th>Screening</th></tr></thead>
                        <tbody>
                            @forelse($donor->donations as $d)
                                <tr>
                                    <td><a href="{{ route('admin.blood-bank.donations.show', $d) }}" class="text-decoration-none">{{ $d->donation_number }}</a></td>
                                    <td>{{ $d->donation_date?->format('d M Y') }}</td>
                                    <td>{{ $d->blood_group }}</td>
                                    <td>{{ $d->unit->unit_number ?? '—' }}</td>
                                    <td><x-status-badge :status="$d->screening_status" domain="screening" size="sm" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No donations yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
