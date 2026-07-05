<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.consultation-specialties.index') }}" class="btn btn-sm {{ request()->routeIs('admin.consultation-specialties.index') ? 'btn-primary' : 'btn-outline-primary' }}">
        <i class="ti ti-layout-board me-1"></i>{{ __('consultation_specialties.admin.profiles') }}
    </a>
    <a href="{{ route('admin.consultation-specialties.mappings.index') }}" class="btn btn-sm {{ request()->routeIs('admin.consultation-specialties.mappings.*') ? 'btn-primary' : 'btn-outline-primary' }}">
        <i class="ti ti-arrows-split me-1"></i>{{ __('consultation_specialties.admin.mappings') }}
    </a>
    @can('consultation-specialties.configure')
        <a href="{{ route('admin.consultation-specialties.doctor-preferences.index') }}" class="btn btn-sm {{ request()->routeIs('admin.consultation-specialties.doctor-preferences.*') ? 'btn-primary' : 'btn-outline-primary' }}">
            <i class="ti ti-user-cog me-1"></i>{{ __('consultation_specialties.admin.doctor_preferences') }}
        </a>
    @endcan
    @isset($profile)
        @if($profile->exists)
            <a href="{{ route('admin.consultation-specialties.sections.index', $profile) }}" class="btn btn-sm {{ request()->routeIs('admin.consultation-specialties.sections.*') ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="ti ti-list-details me-1"></i>{{ __('consultation_specialties.admin.sections') }}
            </a>
            <a href="{{ route('admin.consultation-specialties.favorites.index', $profile) }}" class="btn btn-sm {{ request()->routeIs('admin.consultation-specialties.favorites.*') ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="ti ti-star me-1"></i>{{ __('consultation_specialties.admin.favorites') }}
            </a>
            <a href="{{ route('admin.consultation-specialties.order-sets.index', $profile) }}" class="btn btn-sm {{ request()->routeIs('admin.consultation-specialties.order-sets.*') ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="ti ti-packages me-1"></i>{{ __('consultation_specialties.admin.order_sets') }}
            </a>
        @endif
    @endisset
</div>
