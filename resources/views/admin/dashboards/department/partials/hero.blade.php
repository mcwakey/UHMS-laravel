<div class="card border-0 shadow-sm mb-3 overflow-hidden">
    <div class="card-body position-relative">
        <img src="{{ asset('build/img/bg/bg-01.svg') }}" alt="" class="position-absolute start-0 top-0 opacity-25">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
            <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-xl rounded-circle bg-{{ $theme['accent_class'] ?? 'secondary' }} text-white">
                    <i class="ti {{ $theme['hero_icon'] ?? 'ti-layout-dashboard' }} fs-28"></i>
                </span>
                <div>
                    @if(!empty($dashboard['menu_heading']))
                        <div class="text-uppercase small fw-semibold mb-1 text-{{ $theme['accent_class'] ?? 'secondary' }}" style="letter-spacing:.04em;">{{ $dashboard['menu_heading'] }}</div>
                    @endif
                    <h3 class="fw-bold mb-1">{{ $dashboard['title'] ?? __('dashboards.department.department_dashboard') }}</h3>
                    <div class="text-muted">
                        {{ $dashboard['subtitle'] ?? '' }}
                        <span class="mx-1">&bull;</span>
                        {{ now()->translatedFormat('l, d M Y') }}
                    </div>
                    @if(!empty($dashboard['welcome']) || !empty($dashboard['scope_message']))
                        <div class="mt-1 small">
                            @if(!empty($dashboard['welcome']))<span class="fw-medium">{{ $dashboard['welcome'] }}</span>@endif
                            @if(!empty($dashboard['scope_message']))<span class="text-muted">@if(!empty($dashboard['welcome'])) — @endif{{ $dashboard['scope_message'] }}</span>@endif
                        </div>
                    @endif
                    <div class="mt-2">
                        @if($context->current_department)
                            <span class="badge bg-light text-dark border">{{ __('dashboards.department.current_department') }}: {{ $context->current_department->name }}</span>
                        @endif
                        <span class="badge {{ $theme['badge_class'] ?? 'bg-secondary' }}">{{ $context->department_type_label }}</span>
                        @if(!empty($operational_status))
                            <span class="badge bg-{{ $operational_status['variant'] }} d-inline-flex align-items-center gap-1"><i class="ti ti-pulse"></i>{{ $operational_status['label'] }}</span>
                        @endif
                        @if(!empty($dashboard['shift']))
                            <span class="badge bg-light text-dark border"><i class="ti ti-clock-hour-9 me-1"></i>{{ $dashboard['shift'] }}</span>
                        @endif
                        @if(!empty($dashboard['last_updated']))
                            <span class="text-muted small ms-1"><i class="ti ti-refresh me-1"></i>{{ __('dashboards.department.last_updated', ['time' => $dashboard['last_updated']->isoFormat('HH:mm')]) }}</span>
                        @endif
                        @if($context->is_switched_context)
                            <span class="badge bg-warning text-dark">{{ __('dashboards.department.switched_context') }}</span>
                        @endif
                        @if($context->is_global_context)
                            <span class="badge bg-dark-subtle text-dark">{{ __('dashboards.department.global_context') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($context->can_switch_department)
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="ti ti-building-hospital me-1"></i>{{ __('dashboards.department.switch_department') }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 280px;">
                        @if($context->available_departments->count() > 8)
                            <input type="search" class="form-control form-control-sm mb-2" data-department-switch-search placeholder="{{ __('dashboards.department.available_departments') }}">
                        @endif
                        @foreach($context->available_departments as $availableDepartment)
                            <form method="POST" action="{{ route('admin.my-dashboard.context.store') }}" class="mb-1" data-department-switch-item>
                                @csrf
                                <input type="hidden" name="department_id" value="{{ $availableDepartment->id }}">
                                <button type="submit" class="dropdown-item rounded d-flex justify-content-between align-items-start gap-2 @if($context->current_department_id === $availableDepartment->id) active @endif">
                                    <span>
                                        <span class="d-block fw-medium">{{ $availableDepartment->name }}</span>
                                        <small>{{ $availableDepartment->type?->translatedLabel() ?? __('dashboards.department.generic_type') }}</small>
                                    </span>
                                    @if((bool) ($availableDepartment->pivot?->is_primary ?? false) || $context->user->department_id === $availableDepartment->id)
                                        <span class="badge bg-light text-dark">{{ __('dashboards.department.primary_department') }}</span>
                                    @endif
                                </button>
                            </form>
                        @endforeach
                        @if($context->is_switched_context)
                            <form method="POST" action="{{ route('admin.my-dashboard.context.destroy') }}" class="border-top pt-2 mt-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item rounded text-muted">
                                    <i class="ti ti-restore me-1"></i>{{ __('dashboards.department.clear_department_context') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @endif
                @if(!empty($available_dashboards))
                <form method="GET" class="d-flex align-items-center gap-2">
                    <select name="as" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="{{ __('dashboards.department.preview_dashboard') }}">
                        @foreach($available_dashboards as $value => $label)
                            <option value="{{ $value }}" @selected(($key ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-refresh me-1"></i>{{ __('dashboards.refresh') }}
                </a>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('input', function (event) {
    const search = event.target.closest('[data-department-switch-search]');
    if (!search) return;

    const menu = search.closest('.dropdown-menu');
    const term = search.value.toLowerCase();
    menu.querySelectorAll('[data-department-switch-item]').forEach(function (item) {
        item.classList.toggle('d-none', !item.textContent.toLowerCase().includes(term));
    });
});
</script>
@endpush
@endonce
