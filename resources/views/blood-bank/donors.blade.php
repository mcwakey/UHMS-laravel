@extends('layouts.app')
@section('title', __('blood_bank.blood_donors'))

@php($groups = ['O-','O+','A-','A+','B-','B+','AB-','AB+'])

@section('content')
<x-page-header :title="__('blood_bank.blood_donors')" :description="__('blood_bank.donors_description')" icon="ti-droplet">
    <x-slot:actions>
        @can('blood_bank.donors.manage')
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#registerDonorModal">
                <i class="ti ti-user-plus me-1"></i>{{ __('blood_bank.register_donor') }}
            </button>
        @endcan
        <a href="{{ route('admin.blood-bank.donations.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-droplet-plus me-1"></i>{{ __('blood_bank.donations') }}</a>
        @can('blood_bank.settings.manage')
            <a href="{{ route('admin.blood-bank.storage.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-fridge me-1"></i>{{ __('blood_bank.storage') }}</a>
        @endcan
        <a href="{{ route('admin.blood-bank.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-layout-dashboard me-1"></i>{{ __('blood_bank.dashboard_link') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label">{{ __('common.search') }}</label><input name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('blood_bank.search_placeholder') }}"></div>
            <div class="col-md-2"><label class="form-label">{{ __('blood_bank.blood_group') }}</label><select name="blood_group" class="form-select"><option value="">{{ __('common.all') }}</option>@foreach($groups as $g)<option value="{{ $g }}" @selected(($filters['blood_group'] ?? '') === $g)>{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('blood_bank.screening') }}</label><select name="screening_status" class="form-select"><option value="">{{ __('common.all') }}</option>@foreach(['REGISTERED','QUESTIONNAIRE_PENDING','PHYSICAL_ASSESSMENT_PENDING','ELIGIBILITY_PENDING','ELIGIBLE','TEMPORARILY_DEFERRED','PERMANENTLY_DEFERRED'] as $s)<option value="{{ $s }}" @selected(($filters['screening_status'] ?? '') === $s)>{{ __('statuses.donor_screening.'.strtolower($s)) }}</option>@endforeach</select></div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary flex-fill"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                <a class="btn btn-outline-secondary flex-fill" href="{{ route('admin.blood-bank.donors.index') }}">{{ __('common.clear') }}</a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>{{ __('blood_bank.donor_number') }}</th><th>{{ __('blood_bank.name') }}</th><th>{{ __('blood_bank.group') }}</th><th>{{ __('blood_bank.screening_status') }}</th><th>{{ __('blood_bank.last_donation') }}</th><th class="text-end">{{ __('blood_bank.action') }}</th></tr></thead>
                <tbody>
                    @forelse($donors as $donor)
                        @php($ss = $donor->screening_status ?? 'REGISTERED')
                        <tr>
                            <td class="fw-semibold">{{ $donor->donor_number }}</td>
                            <td>
                                <a href="{{ route('admin.blood-bank.donors.show', $donor) }}" class="text-decoration-none fw-medium">{{ $donor->full_name }}</a>
                                <div class="small text-muted">{{ $donor->phone ?? '—' }}</div>
                            </td>
                            <td>@if($donor->blood_group)<span class="badge bg-red-lt">{{ $donor->blood_group }}</span>@else<span class="text-muted">{{ __('blood_bank.unknown') }}</span>@endif</td>
                            <td>
                                <x-status-badge :status="$ss" domain="donor_screening" />
                                @if($donor->latestScreening?->deferral_reason)<div class="small text-muted text-truncate" style="max-width:260px">{{ $donor->latestScreening->deferral_reason }}</div>@endif
                            </td>
                            <td>{{ $donor->last_donation_at?->format('d M Y') ?? '—' }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.blood-bank.donors.show', $donor) }}">
                                    <i class="ti ti-clipboard-heart me-1"></i>{{ __('blood_bank.profile_screening') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="ti-users" :title="__('blood_bank.no_donors_title')" :message="__('blood_bank.no_donors_message')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($donors->hasPages())<div class="card-footer">{{ $donors->links() }}</div>@endif
</div>

@can('blood_bank.donors.manage')
{{-- Register Donor modal --}}
<div class="modal fade" id="registerDonorModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.blood-bank.donors.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-user-plus me-2 text-primary"></i>{{ __('blood_bank.register_blood_donor') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.first_name') }} <span class="text-danger">*</span></label><input name="first_name" class="form-control" value="{{ old('first_name') }}" required></div>
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.last_name') }} <span class="text-danger">*</span></label><input name="last_name" class="form-control" value="{{ old('last_name') }}" required></div>
                        <div class="col-md-4"><label class="form-label">{{ __('blood_bank.blood_group') }}</label><select name="blood_group" class="form-select"><option value="">{{ __('blood_bank.unknown') }}</option>@foreach($groups as $g)<option value="{{ $g }}" @selected(old('blood_group')===$g)>{{ $g }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">{{ __('blood_bank.gender') }}</label><select name="gender" class="form-select"><option value="">—</option>@foreach(['Male','Female','Other'] as $gn)<option value="{{ $gn }}" @selected(old('gender')===$gn)>{{ __("common.gender_".strtolower($gn)) }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">{{ __('blood_bank.date_of_birth') }}</label><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}"></div>
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.phone') }}</label><input name="phone" class="form-control" value="{{ old('phone') }}"></div>
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.email') }}</label><input type="email" name="email" class="form-control" value="{{ old('email') }}"></div>
                        <div class="col-12"><label class="form-label">{{ __('blood_bank.address') }}</label><input name="address" class="form-control" value="{{ old('address') }}"></div>
                    </div>
                    <p class="small text-muted mt-3 mb-0"><i class="ti ti-info-circle me-1"></i>{{ __('blood_bank.after_registering_help') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('blood_bank.save_donor') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any() && old('first_name') !== null)
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',function(){var m=document.getElementById('registerDonorModal');if(m&&window.bootstrap){bootstrap.Modal.getOrCreateInstance(m).show();}});</script>
@endpush
@endif
@endcan
@endsection
