@php
    $stripVisit = $visit ?? null;
    $stripInsurance = $visitInsurance ?? $stripVisit?->visitInsurance;
    $stripProvider = $stripInsurance?->insuranceProvider;
    $stripTier = $stripInsurance?->insuranceTier;
    $stripHasRealInsurance = $stripInsurance
        && $stripInsurance->is_active
        && $stripProvider
        && ! $stripProvider->is_default;
    $stripClass = trim('visit-insurance-strip '.($class ?? ''));
@endphp

@once
@push('styles')
<style>
    .visit-insurance-strip { flex:0 0 100%; border-top:1px solid var(--bs-border-color); padding-top:.7rem;
        display:flex; align-items:center; gap:.65rem; color:var(--bs-secondary-color); font-size:.82rem; min-width:0; }
    .visit-insurance-strip__icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center;
        justify-content:center; flex-shrink:0; }
    .visit-insurance-strip__main { min-width:0; display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; }
    .visit-insurance-strip__sub { color:var(--bs-secondary-color); }
</style>
@endpush
@endonce

<div class="{{ $stripClass }}">
    @if($stripHasRealInsurance)
        <div class="visit-insurance-strip__icon bg-success bg-opacity-10 text-success">
            <i class="ti ti-shield-check"></i>
        </div>
        <div class="visit-insurance-strip__main">
            <span class="fw-semibold text-body text-truncate">{{ $stripProvider->name }}</span>
            @if($stripInsurance->is_expired)
                <span class="badge bg-danger fs-11">Expired</span>
            @elseif($stripInsurance->is_valid)
                <span class="badge bg-success fs-11">Active</span>
            @else
                <span class="badge bg-secondary fs-11">Inactive</span>
            @endif
            @if($stripTier?->name)
                <span class="visit-insurance-strip__sub">{{ $stripTier->name }}</span>
            @endif
            @if($stripInsurance->membership_number)
                <span class="visit-insurance-strip__sub">#<x-patient-protected-field field="membership_number" :value="$stripInsurance->membership_number" /></span>
            @endif
        </div>
    @else
        <div class="visit-insurance-strip__icon bg-secondary bg-opacity-10 text-secondary">
            <i class="ti ti-cash"></i>
        </div>
        <div class="visit-insurance-strip__main">
            <span class="fw-semibold text-body">Cash &amp; Carry</span>
            <span class="visit-insurance-strip__sub">No active insurance for this visit</span>
        </div>
    @endif
</div>
