<div class="tab-pane fade {{ ($activeTabTarget ?? null) === ($section['tab_target'] ?? null) ? 'show active' : '' }}" id="{{ $section['tab_target'] }}" role="tabpanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">
                <i class="ti {{ $section['icon'] ?? 'ti-layout-board' }} me-1"></i>{{ $section['translated_label'] ?? $section['label'] }}
            </h6>
            <div class="d-flex gap-1">
                <span class="badge bg-info-subtle text-info">{{ __('consultation_specialties.workspace.specialist_section') }}</span>
                @if($section['is_required'] ?? false)
                    <span class="badge bg-warning text-dark">{{ __('consultation_specialties.workspace.required') }}</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="text-center text-muted py-4">
                <i class="ti {{ $section['icon'] ?? 'ti-layout-board' }} fs-1 d-block mb-2"></i>
                {{ __('consultation_specialties.workspace.no_structured_data') }}
            </div>
        </div>
    </div>
</div>
