@props([
    'amount' => 0,          // total outstanding for the patient
    'showAmount' => false,  // caller resolves permission once and passes it in
])

@php $amount = (float) $amount; @endphp

@if($amount > 0.009)
    <span {{ $attributes->merge(['class' => 'badge bg-warning-subtle text-warning']) }}
          title="{{ __('billing.total_patient_outstanding') }}">
        <i class="ti ti-alert-triangle me-1"></i>@if($showAmount){{ __('billing.outstanding') }}: ₵{{ number_format($amount, 2) }}@else{{ __('billing.outstanding_balance_exists') }}@endif
    </span>
@endif
