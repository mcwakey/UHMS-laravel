@extends('layouts.app')
@section('title', __('consultation_specialties.admin.sections'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1"><h4 class="fw-bold mb-0"><i class="ti ti-list-details me-2"></i>{{ __('consultation_specialties.admin.sections') }} · {{ $profile->name }}</h4></div>
    <a href="{{ route('admin.consultation-specialties.show', $profile) }}" class="btn btn-outline-secondary btn-sm">{{ __('consultation_specialties.admin.back') }}</a>
</div>
@include('admin.consultation-specialties.partials.flash')
@include('admin.consultation-specialties.partials.nav')

<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('consultation_specialties.admin.create') }}</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.consultation-specialties.sections.store', $profile) }}" class="row g-2 align-items-end">
            @csrf
            @include('admin.consultation-specialties.partials.section-fields', ['section' => null])
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="ti ti-plus me-1"></i>{{ __('consultation_specialties.admin.add') }}</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>{{ __('consultation_specialties.admin.section_key') }}</th><th>{{ __('consultation_specialties.admin.label') }}</th><th>{{ __('consultation_specialties.admin.component') }}</th><th>{{ __('consultation_specialties.admin.sort_order') }}</th><th>{{ __('consultation_specialties.admin.status') }}</th><th class="text-end">{{ __('consultation_specialties.admin.actions') }}</th></tr></thead>
                <tbody>
                @forelse($profile->sections as $section)
                    <tr>
                        <form method="POST" action="{{ route('admin.consultation-specialties.sections.update', [$profile, $section]) }}">
                            @csrf @method('PATCH')
                            @include('admin.consultation-specialties.partials.section-fields', ['section' => $section])
                            <td class="text-end">
                                @can('consultation-specialties.configure')<button class="btn btn-sm btn-primary">{{ __('consultation_specialties.admin.update') }}</button>@endcan
                                @can('consultation-specialties.delete')
                                    <button form="delete-section-{{ $section->id }}" class="btn btn-sm btn-outline-danger">{{ __('consultation_specialties.admin.delete') }}</button>
                                @endcan
                            </td>
                        </form>
                        <form id="delete-section-{{ $section->id }}" method="POST" action="{{ route('admin.consultation-specialties.sections.destroy', [$profile, $section]) }}">@csrf @method('DELETE')</form>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('consultation_specialties.admin.no_records') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
