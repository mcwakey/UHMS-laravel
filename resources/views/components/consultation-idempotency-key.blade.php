@props(['action'])

<input type="hidden" name="_idempotency_key" data-idempotency-action="{{ $action }}" value="{{ $action }}-{{ (string) \Illuminate\Support\Str::uuid() }}">
