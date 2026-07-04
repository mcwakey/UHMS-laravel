@extends('layouts.app')
@section('title', __('maternity.pregnancy_profiles'))

@section('content')
<x-page-header :title="__('maternity.pregnancy_profiles')" icon="ti-heartbeat">
    <x-slot:actions>
        @can('maternity.pregnancy.create')
        <a href="{{ route('admin.maternity.pregnancies.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>{{ __('maternity.new_pregnancy_profile') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5"><label class="form-label small text-muted mb-1">{{ __('common.search') }}</label><input name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('maternity.search_profiles') }}"></div>
            <div class="col-md-3"><label class="form-label small text-muted mb-1">{{ __('maternity.profile_status') }}</label><select name="status" class="form-select"><option value="">{{ __('common.all_statuses') }}</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ti ti-search"></i></button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>{{ __('maternity.patient') }}</th><th>G/P/A/L</th><th>{{ __('maternity.lmp') }}</th><th>{{ __('maternity.edd') }}</th><th>{{ __('maternity.gestational_age') }}</th><th>{{ __('maternity.profile_status') }}</th><th>{{ __('maternity.linked_context') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($profiles as $profile)
                    <tr>
                        <td><strong>{{ $profile->patient?->full_name }}</strong><br><small class="text-muted">{{ $profile->patient?->patient_number }} · {{ $profile->patient?->age }} yrs</small></td>
                        <td>{{ $profile->gravida ?? '—' }}/{{ $profile->para ?? '—' }}/{{ $profile->abortions ?? '—' }}/{{ $profile->living_children ?? '—' }}</td>
                        <td>{{ $profile->last_menstrual_period?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $profile->estimated_due_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $profile->gestational_age_weeks !== null ? $profile->gestational_age_weeks.'w '.$profile->gestational_age_days.'d' : '—' }}</td>
                        <td><span class="badge bg-{{ $profile->profile_status->color() }}">{{ $profile->profile_status->label() }}</span></td>
                        <td><small class="text-muted">{{ $profile->visit?->visit_number ?? '' }} {{ $profile->admission?->admission_number ?? '' }}</small></td>
                        <td class="text-end"><a href="{{ route('admin.maternity.pregnancies.show', $profile) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="8"><x-empty-state icon="ti-heartbeat" :title="__('maternity.no_profiles')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($profiles->hasPages())<div class="card-footer">{{ $profiles->links() }}</div>@endif
</div>
@endsection
