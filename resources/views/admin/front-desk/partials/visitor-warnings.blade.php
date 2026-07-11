@php($warnings = $warnings ?? [])
@if(!empty($warnings))
<div class="alert alert-warning">
    <div class="fw-semibold mb-1"><i class="ti ti-alert-triangle me-1"></i>{{ __('front_desk.warnings.title') }}</div>
    <ul class="mb-1 ps-3">
        @foreach($warnings as $warning)
        <li>{{ $warning['message'] }}</li>
        @endforeach
    </ul>
    <small class="text-muted">{{ __('front_desk.warnings.advisory_note') }}</small>
</div>
@endif
