{{-- Small inline reason-required action form. Params: $route, $label, $btn --}}
<form method="POST" action="{{ $route }}" class="d-flex gap-2">
    @csrf
    <input type="text" name="reason" class="form-control form-control-sm" maxlength="1000" placeholder="{{ __('visit_payment_arrangement.history.reason_code') }}" required>
    <button type="submit" class="btn btn-sm {{ $btn }} text-nowrap">{{ $label }}</button>
</form>
