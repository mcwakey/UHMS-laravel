{{-- Backward-compatible wrapper. Prefer <x-patient-card> in new views. --}}
<x-patient-card
    :patient="$patient ?? null"
    :visit="$visit ?? null"
    :visit-insurance="$visitInsurance ?? null"
    :compact="$compact ?? false"
/>
