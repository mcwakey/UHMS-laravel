{{--
    Phase 14R.5.1 — one-way LMP adoption for pregnancy dating.

    The value is read SERVER-SIDE from the persisted menstrual_history entry;
    nothing submitted here can change it. The consultation entry is never
    modified and the profile LMP is never synced back.

    Expects: $action
--}}
<div class="alert alert-light border py-2 px-3 small mb-0">
    <div>{{ __('maternity_handoffs.modal.adopt_lmp_server_side') }}</div>
    @if (! empty($action->context['saved_lmp']))
        <div class="mt-1">
            <strong>{{ __('maternity_handoffs.modal.saved_lmp') }}:</strong>
            {{ $action->context['saved_lmp'] }}
        </div>
    @endif
</div>
