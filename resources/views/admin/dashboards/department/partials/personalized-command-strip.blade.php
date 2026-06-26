@php
    $personalizationKey = $dashboardPersonalization['key'] ?? ($key ?? 'generic');
    $translationRoot = 'dashboards.department.personalization.'.$personalizationKey;
    $userName = $context->user->first_name ?? $context->user->name ?? '';
    $departmentName = $context->current_department?->name ?? $context->department?->name ?? __('dashboards.department.global_department_view');
    $accent = $theme['accent_class'] ?? 'primary';
    $icon = match ($personalizationKey) {
        'management' => 'ti-chart-arcs',
        'consultation' => 'ti-stethoscope',
        'emergency' => 'ti-ambulance',
        'admission' => 'ti-bed',
        'pharmacy' => 'ti-pill',
        'investigation' => 'ti-microscope',
        'theatre' => 'ti-first-aid-kit',
        'billing' => 'ti-receipt',
        'claims' => 'ti-file-invoice',
        'stock' => 'ti-packages',
        'blood_bank' => 'ti-droplet-heart',
        'accounting' => 'ti-calculator',
        'hr' => 'ti-users-group',
        'reception' => 'ti-door-enter',
        default => 'ti-layout-dashboard',
    };
    $focusItems = [
        __($translationRoot.'.focus_1'),
        __($translationRoot.'.focus_2'),
        __($translationRoot.'.focus_3'),
    ];
    $metricSnapshot = collect($primary_cards ?? [])->take(3);
@endphp

<div class="card border-0 shadow-sm mb-3 department-personality-strip">
    <div class="card-body">
        <div class="row g-3 align-items-stretch">
            <div class="col-xl-5">
                <div class="d-flex gap-3 h-100">
                    <span class="avatar avatar-xl rounded-circle bg-{{ $accent }} text-white flex-shrink-0">
                        <i class="ti {{ $icon }} fs-28"></i>
                    </span>
                    <div>
                        <div class="text-uppercase text-muted small fw-semibold mb-1">{{ __($translationRoot.'.eyebrow', ['department' => $departmentName]) }}</div>
                        <h4 class="fw-bold mb-1">{{ __($translationRoot.'.headline', ['name' => $userName]) }}</h4>
                        <p class="text-muted mb-0">{{ __($translationRoot.'.brief') }}</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="h-100 rounded-2 border bg-light p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="ti ti-target-arrow text-{{ $accent }}"></i>
                        <span class="fw-semibold">{{ __('dashboards.department.personalization.focus_heading') }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($focusItems as $focus)
                            <span class="badge bg-white text-dark border fw-normal">{{ $focus }}</span>
                        @endforeach
                    </div>
                    <div class="small text-muted mt-3">
                        <i class="ti ti-clock-hour-4 me-1"></i>{{ __($translationRoot.'.rhythm') }}
                    </div>
                </div>
            </div>

            <div class="col-xl-3">
                <div class="h-100 rounded-2 border p-3">
                    <div class="fw-semibold mb-2">{{ __('dashboards.department.personalization.snapshot_heading') }}</div>
                    @forelse($metricSnapshot as $card)
                        @php
                            $value = $card['value'] ?? 0;
                            $restricted = is_array($value) && ($value['restricted'] ?? false);
                            $display = $restricted
                                ? __('dashboards.department.restricted')
                                : (($card['format'] ?? null) === 'currency' ? 'GHS '.number_format((float) $value, 2) : number_format((float) $value));
                        @endphp
                        <div class="d-flex justify-content-between gap-2 small mb-1">
                            <span class="text-muted text-truncate">{{ $card['title'] ?? '' }}</span>
                            <span class="fw-semibold text-dark">{{ $display }}</span>
                        </div>
                    @empty
                        <div class="text-muted small">{{ __('dashboards.department.no_department_data') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .department-personality-strip {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }
    .department-personality-strip .badge {
        white-space: normal;
        text-align: left;
    }
</style>
@endpush
@endonce
