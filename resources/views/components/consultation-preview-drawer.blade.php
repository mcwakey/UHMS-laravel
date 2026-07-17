@props([
    'id' => 'consultationPreviewOffcanvas',
    'preview',
    'title' => null,
    'showPrint' => true,
])

@php
    $visit = $preview['visit'];
    $labelId = $id.'Label';
    $title ??= __('consultations.workspace.preview');
@endphp

@once
    @push('styles')
        <style>
            .consultation-preview-offcanvas { width: min(100vw, 1120px) !important; }
            .consultation-preview-offcanvas .offcanvas-body { background: #f8fafc; }
        </style>
    @endpush
@endonce

<div class="offcanvas offcanvas-end consultation-preview-offcanvas" tabindex="-1" id="{{ $id }}" aria-labelledby="{{ $labelId }}">
    <div class="offcanvas-header border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h5 class="offcanvas-title fw-bold mb-0" id="{{ $labelId }}">
                <i class="ti ti-history me-1"></i>{{ $title }}
            </h5>
            <div class="text-muted small">{{ $visit->visit_number }} &middot; {{ $visit->patient?->full_name }}</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($showPrint)
                <button type="button" class="btn btn-primary btn-sm" data-consultation-action="print">
                    <i class="ti ti-printer me-1"></i>{{ __('consultations.history.print_summary') }}
                </button>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('common.close') }}"></button>
        </div>
    </div>
    <div class="offcanvas-body">
        <x-consultation-preview
            :visit="$preview['visit']"
            :generated-at="$preview['generatedAt']"
            :sessions="$preview['sessions']"
            :contributors="$preview['contributors']"
            :session-summaries="$preview['sessionSummaries']"
            :lab-requests="$preview['labRequests']"
            :procedure-requests="$preview['procedureRequests']"
        />
    </div>
</div>
