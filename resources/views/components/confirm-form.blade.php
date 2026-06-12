@props([
    'action',
    'method' => 'POST',
    'buttonLabel' => null,
    'buttonClass' => 'btn btn-danger',
    'icon' => null,
    'confirmTitle' => null,
    'confirmText' => null,
    'confirmButton' => null,
    'cancelButton' => null,
    'requireReason' => false,
    'reasonName' => 'reason',
    'reasonPlaceholder' => null,
    'disabled' => false,
    'disabledReason' => null,
])

@php
    $buttonLabel ??= __('common.confirm');
    $confirmTitle ??= __('common.are_you_sure');
    $confirmText ??= __('common.please_confirm_action');
    $confirmButton ??= __('common.confirm');
    $cancelButton ??= __('common.cancel');
    $formId = 'cf_'.\Illuminate\Support\Str::random(8);
@endphp

{{--
    Destructive/high-risk action as a confirmed form. Submits only after a
    SweetAlert2 confirmation (with a native confirm() fallback). When
    require-reason is set, a reason is collected and posted as `reasonName`.
--}}
<form id="{{ $formId }}" method="POST" action="{{ $action }}" {{ $attributes->merge(['class' => 'd-inline']) }}>
    @csrf
    @if(strtoupper($method) !== 'POST')
        @method($method)
    @endif
    @if($requireReason)
        <input type="hidden" name="{{ $reasonName }}" value="">
    @endif
    <button type="button" class="{{ $buttonClass }}"
            @disabled($disabled)
            @if($disabled && $disabledReason) title="{{ $disabledReason }}" @endif
            onclick="window.uhmsConfirmSubmit && window.uhmsConfirmSubmit(this)"
            data-form="{{ $formId }}"
            data-title="{{ $confirmTitle }}"
            data-text="{{ $confirmText }}"
            data-confirm="{{ $confirmButton }}"
            data-cancel="{{ $cancelButton }}"
            data-require-reason="{{ $requireReason ? '1' : '0' }}"
            data-reason-name="{{ $reasonName }}"
            data-reason-placeholder="{{ $reasonPlaceholder ?? __('common.enter_a_reason') }}">
        @if($icon)<i class="ti {{ $icon }} me-1"></i>@endif{{ $buttonLabel }}
    </button>
</form>

@once
    @push('scripts')
    <script>
    window.uhmsConfirmSubmit = function (btn) {
        var form = document.getElementById(btn.dataset.form);
        if (!form) return;
        var requireReason = btn.dataset.requireReason === '1';

        var submit = function (reason) {
            if (requireReason) {
                var field = form.querySelector('[name="' + btn.dataset.reasonName + '"]');
                if (field) field.value = reason || '';
            }
            btn.disabled = true;            // prevent double submit
            if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
        };

        if (typeof Swal === 'undefined') {
            if (window.confirm(btn.dataset.title + '\n\n' + btn.dataset.text)) {
                if (requireReason) {
                    var r = window.prompt(btn.dataset.reasonPlaceholder);
                    if (r === null || !r.trim()) return;
                    submit(r);
                } else {
                    submit(null);
                }
            }
            return;
        }

        var opts = {
            title: btn.dataset.title,
            text: btn.dataset.text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: btn.dataset.confirm,
            cancelButtonText: btn.dataset.cancel,
            confirmButtonColor: '#dc3545',
            reverseButtons: true,
        };
        if (requireReason) {
            opts.input = 'text';
            opts.inputPlaceholder = btn.dataset.reasonPlaceholder;
            opts.inputValidator = function (v) { if (!v || !v.trim()) return @json(__('common.reason_required')); };
        }
        Swal.fire(opts).then(function (res) {
            if (res.isConfirmed) submit(requireReason ? res.value : null);
        });
    };
    </script>
    @endpush
@endonce
