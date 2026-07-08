@extends('layouts.app')
@section('title', $profile->name)

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="{{ $profile->icon ?: 'ti ti-layout-board' }} me-2"></i>{{ $profile->name }}</h4>
        <div class="text-muted small"><code>{{ $profile->code }}</code> · {{ $profile->department_type ?: __('consultation_specialties.admin.none') }}</div>
    </div>
    @can('consultation-specialties.update')
        <a href="{{ route('admin.consultation-specialties.edit', $profile) }}" class="btn btn-primary btn-sm"><i class="ti ti-edit me-1"></i>{{ __('consultation_specialties.admin.edit') }}</a>
    @endcan
</div>

@include('admin.consultation-specialties.partials.flash')
@include('admin.consultation-specialties.partials.nav')

<div class="row g-3 mb-3">
    @foreach([
        __('consultation_specialties.admin.sections') => $profile->sections_count,
        __('consultation_specialties.admin.favorites') => $profile->favorites_count,
        __('consultation_specialties.admin.order_sets') => $profile->order_sets_count,
        __('consultation_specialties.admin.mappings') => $profile->mappings_count,
        __('consultation_specialties.admin.doctor_preferences') => $profile->doctor_preferences_count,
    ] as $label => $count)
        <div class="col-md">
            <div class="card"><div class="card-body py-3"><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $count }}</div></div></div>
        </div>
    @endforeach
</div>

@if(! $profile->is_active)
    <div class="alert alert-warning">{{ __('consultation_specialties.admin.inactive_warning') }}</div>
@endif
@if(! $profile->isGeneral() && $profile->sections_count === 0)
    <div class="alert alert-warning">{{ __('consultation_specialties.admin.no_visible_sections_warning') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><h5 class="card-title mb-0">{{ __('consultation_specialties.admin.sections') }}</h5><a href="{{ route('admin.consultation-specialties.sections.index', $profile) }}" class="btn btn-sm btn-outline-primary">{{ __('consultation_specialties.admin.manage') }}</a></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                    @forelse($profile->sections as $section)
                        @php($aliasTarget = ($sectionAliasMap ?? [])[$section->section_key] ?? null)
                        @php($isCanonicalComplaints = $section->section_key === 'complaints' && ! empty($complaintDisplayLabel) && $complaintDisplayLabel !== __('consultation_specialties.sections.complaints'))
                        <tr>
                            <td><code>{{ $section->section_key }}</code></td>
                            <td>{{ $section->label }}</td>
                            <td class="text-end">
                                @if($aliasTarget)
                                    <span class="badge bg-info-subtle text-info" title="{{ __('consultation_specialties.admin.hidden_from_doctor_workspace') }}">{{ __('consultation_specialties.admin.maps_to', ['section' => __('consultation_specialties.sections.'.$aliasTarget)]) }}</span>
                                @endif
                                @if($isCanonicalComplaints)
                                    <span class="badge bg-primary-subtle text-primary">{{ __('consultation_specialties.admin.displayed_as', ['label' => $complaintDisplayLabel]) }}</span>
                                @endif
                                <span class="badge bg-{{ $section->is_visible ? 'success' : 'secondary' }}">{{ $section->is_visible ? __('consultation_specialties.admin.visible') : __('consultation_specialties.admin.hidden') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-muted py-3">{{ __('consultation_specialties.admin.no_records') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><h5 class="card-title mb-0">{{ __('consultation_specialties.admin.order_sets') }}</h5><a href="{{ route('admin.consultation-specialties.order-sets.index', $profile) }}" class="btn btn-sm btn-outline-primary">{{ __('consultation_specialties.admin.manage') }}</a></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                    @forelse($profile->orderSets as $orderSet)
                        <tr><td><code>{{ $orderSet->code }}</code></td><td>{{ $orderSet->name }}</td><td class="text-end"><span class="badge bg-soft-primary">{{ $orderSet->items_count ?? 0 }}</span></td></tr>
                    @empty
                        <tr><td class="text-center text-muted py-3">{{ __('consultation_specialties.admin.no_records') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
