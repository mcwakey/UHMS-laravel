@props([
    'label' => null,
    'icon' => 'ti-dots-vertical',
    'align' => 'end',
    'size' => 'sm',
])

@php $label ??= __('common.actions'); @endphp

{{--
    Standard row-action dropdown. Put `dropdown-item` links/buttons (and
    <x-confirm-form> for destructive actions) in the default slot. Keep the
    order View → Edit → Print → Cancel/Delete, danger last, behind @can checks.
--}}
<div {{ $attributes->merge(['class' => 'dropdown']) }}>
    <button class="btn btn-{{ $size }} btn-white border dropdown-toggle drop-arrow-none" type="button"
            data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ $label }}">
        <i class="ti {{ $icon }}" aria-hidden="true"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-{{ $align }}">
        {{ $slot }}
    </div>
</div>
