@extends('layouts.app')
@section('title', __('consultation_specialties.admin.doctor_preferences'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1"><h4 class="fw-bold mb-0"><i class="ti ti-user-cog me-2"></i>{{ __('consultation_specialties.admin.doctor_preferences') }}</h4></div>
</div>
@include('admin.consultation-specialties.partials.flash')
@include('admin.consultation-specialties.partials.nav')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5"><label class="form-label">{{ __('consultation_specialties.admin.search') }}</label><input name="search" class="form-control" value="{{ request('search') }}"></div>
            <div class="col-md-3"><button class="btn btn-outline-primary"><i class="ti ti-search me-1"></i>{{ __('consultation_specialties.admin.filters') }}</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>{{ __('consultation_specialties.admin.doctor') }}</th><th>{{ __('consultation_specialties.admin.profile') }}</th><th>{{ __('consultation_specialties.admin.department') }}</th><th>{{ __('consultation_specialties.admin.pinned_actions') }}</th><th>{{ __('consultation_specialties.admin.layout') }}</th><th class="text-end">{{ __('consultation_specialties.admin.actions') }}</th></tr></thead>
                <tbody>
                @forelse($preferences as $preference)
                    <tr>
                        <td>{{ $preference->user?->full_name ?? __('consultation_specialties.admin.none') }}</td>
                        <td>{{ $preference->defaultSpecialtyProfile?->name ?? __('consultation_specialties.admin.none') }}</td>
                        <td>{{ $preference->defaultDepartment?->name ?? __('consultation_specialties.admin.none') }}</td>
                        <td><span class="badge bg-soft-primary">{{ count($preference->pinned_actions ?? []) }}</span></td>
                        <td>{{ $preference->preferred_layout ?: __('consultation_specialties.admin.default_layout') }} @if($preference->compact_mode)<span class="badge bg-info">{{ __('consultation_specialties.admin.compact_mode') }}</span>@endif</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.consultation-specialties.doctor-preferences.destroy', $preference) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">{{ __('consultation_specialties.admin.reset') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('consultation_specialties.admin.no_records') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($preferences->hasPages())<div class="card-footer">{{ $preferences->links() }}</div>@endif
</div>
@endsection
