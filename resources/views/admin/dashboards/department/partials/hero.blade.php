<div class="card border-0 shadow-sm mb-3 overflow-hidden">
    <div class="card-body position-relative">
        <img src="{{ asset('build/img/bg/bg-01.svg') }}" alt="" class="position-absolute start-0 top-0 opacity-25">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
            <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-xl rounded-circle bg-{{ $theme['accent_class'] ?? 'secondary' }} text-white">
                    <i class="ti {{ $theme['hero_icon'] ?? 'ti-layout-dashboard' }} fs-28"></i>
                </span>
                <div>
                    <h3 class="fw-bold mb-1">{{ $dashboard['title'] ?? __('dashboards.department.department_dashboard') }}</h3>
                    <div class="text-muted">
                        {{ $dashboard['subtitle'] ?? '' }}
                        <span class="mx-1">&bull;</span>
                        {{ now()->translatedFormat('l, d M Y') }}
                    </div>
                    <div class="mt-2">
                        <span class="badge {{ $theme['badge_class'] ?? 'bg-secondary' }}">{{ $context->department_type_label }}</span>
                        @if($context->is_global_context)
                            <span class="badge bg-dark-subtle text-dark">{{ __('dashboards.department.global_context') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
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
