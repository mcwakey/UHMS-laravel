@extends('layouts.app')
@section('title', __('consultations.history.page_title', ['visit' => $visit->visit_number]))

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 no-print">
    <a href="{{ route('admin.consultations.show', $visit) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>{{ __('consultations.history.back_to_consultation') }}
    </a>
    <button type="button" class="btn btn-primary btn-sm ms-auto" onclick="window.print()">
        <i class="ti ti-printer me-1"></i>{{ __('consultations.history.print_summary') }}
    </button>
</div>

<x-consultation-preview
    :visit="$visit"
    :generated-at="$generatedAt"
    :sessions="$sessions"
    :contributors="$contributors"
    :session-summaries="$sessionSummaries"
    :lab-requests="$labRequests"
    :procedure-requests="$procedureRequests"
/>
@endsection
