@props([
    'action' => null,
    'method' => 'GET',
    'resetUrl' => null,
    'title' => null,
    'icon' => 'ti-filter',
    'collapsible' => false,
    'applyLabel' => null,
    'resetLabel' => null,
    'showApply' => true,
    'showReset' => true,
    'autoSubmit' => true,
    'autoSubmitDelay' => 400,
    'ajax' => false,
    'ajaxTarget' => null,
    'rowClass' => 'row g-2 align-items-end',
    'actionsClass' => 'col-md-auto d-flex gap-2 align-items-end ms-md-auto',
    'bodyClass' => 'card-body py-2',
])

@php
    $title ??= __('common.filters');
    $applyLabel ??= __('common.apply_filters');
    $resetLabel ??= __('common.reset');
    $action = $action ?: url()->current();
    $resetUrl = $resetUrl ?: url()->current();
    $isGet = strtoupper($method) === 'GET';
    $bodyId = 'filterbar_'.\Illuminate\Support\Str::random(6);
@endphp

{{--
    Standard filter/search wrapper. Place filter fields (col-* divs) in the
    default slot. Apply + Reset buttons are added automatically, or provide an
    actions slot for page-specific controls.
--}}
<form method="{{ $isGet ? 'GET' : 'POST' }}"
      action="{{ $action }}"
      data-filter-auto-submit="{{ $autoSubmit ? 'true' : 'false' }}"
      data-filter-auto-submit-delay="{{ $autoSubmitDelay }}"
      @if($ajax && $ajaxTarget) data-filter-ajax-target="{{ $ajaxTarget }}" @endif
      {{ $attributes->merge(['class' => 'card mb-3 uhms-filter-bar']) }}>
    @unless($isGet)
        @csrf
        @method($method)
    @endunless

    @if($collapsible)
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold small"><i class="ti {{ $icon }} me-1"></i>{{ $title }}</span>
            <button class="btn btn-sm btn-link p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $bodyId }}">
                {{ __('common.toggle') }}
            </button>
        </div>
    @endif

    <div class="{{ $bodyClass }} {{ $collapsible ? 'collapse show' : '' }}" id="{{ $bodyId }}">
        <div class="{{ $rowClass }}">
            {{ $slot }}
            @isset($actions)
                <div class="{{ $actionsClass }}">{{ $actions }}</div>
            @else
                @if($showApply || $showReset)
                    <div class="{{ $actionsClass }}">
                        @if($showApply)
                            <button type="submit" class="btn btn-primary"><i class="ti {{ $icon }} me-1"></i>{{ $applyLabel }}</button>
                        @endif
                        @if($showReset)
                            <a href="{{ $resetUrl }}" class="btn btn-outline-secondary">{{ $resetLabel }}</a>
                        @endif
                    </div>
                @endif
            @endisset
        </div>
    </div>
</form>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.uhms-filter-bar[data-filter-auto-submit="true"]').forEach(function (form) {
                    if (form.dataset.uhmsFilterAutoSubmitReady) {
                        return;
                    }

                    let filterTimer = null;
                    const delay = parseInt(form.dataset.filterAutoSubmitDelay || '400', 10);
                    const ajaxTargetSelector = form.dataset.filterAjaxTarget || '';
                    const ajaxTarget = ajaxTargetSelector ? document.querySelector(ajaxTargetSelector) : null;

                    if (!ajaxTarget && form.hasAttribute('data-auto-filter-form')) {
                        return;
                    }

                    form.dataset.uhmsFilterAutoSubmitReady = 'true';

                    const setLoading = function (isLoading) {
                        if (!ajaxTarget) return;
                        ajaxTarget.classList.toggle('opacity-50', isLoading);
                        ajaxTarget.style.pointerEvents = isLoading ? 'none' : '';
                    };

                    const replaceAjaxTarget = function (html) {
                        if (!ajaxTarget || !ajaxTargetSelector) return;

                        const parsed = new DOMParser().parseFromString(html, 'text/html');
                        const replacement = parsed.querySelector(ajaxTargetSelector);
                        if (!replacement) return;

                        ajaxTarget.innerHTML = replacement.innerHTML;
                    };

                    const buildGetUrl = function () {
                        const url = new URL(form.action, window.location.origin);
                        const params = new URLSearchParams(new FormData(form));
                        url.search = params.toString();

                        return url;
                    };

                    const loadAjaxUrl = async function (url) {
                        if (!ajaxTarget) {
                            return false;
                        }

                        setLoading(true);

                        try {
                            const response = await fetch(url.toString(), {
                                headers: {
                                    'Accept': 'text/html',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            if (!response.ok) {
                                window.location.href = url.toString();
                                return true;
                            }

                            replaceAjaxTarget(await response.text());
                            window.history.replaceState({}, '', url.toString());
                            return true;
                        } catch (error) {
                            window.location.href = url.toString();
                            return true;
                        } finally {
                            setLoading(false);
                        }
                    };

                    const submitAjax = function () {
                        return loadAjaxUrl(buildGetUrl());
                    };

                    const clearFilterControls = function () {
                        form.querySelectorAll('input, select, textarea').forEach(function (field) {
                            if (field.type === 'checkbox' || field.type === 'radio') {
                                field.checked = false;
                                return;
                            }

                            if (field.tagName === 'SELECT') {
                                field.selectedIndex = 0;
                                return;
                            }

                            field.value = '';
                        });

                        form.querySelectorAll('.uhms-date-range-filter').forEach(function (picker) {
                            const label = picker.querySelector('.reportrange-picker-field');
                            if (label) {
                                label.textContent = 'Select date range';
                            }
                        });
                    };

                    const submitForm = function () {
                        if (ajaxTarget) {
                            submitAjax();
                            return;
                        }

                        if (form.requestSubmit) {
                            form.requestSubmit();
                            return;
                        }

                        form.submit();
                    };

                    if (ajaxTarget) {
                        form.addEventListener('submit', function (event) {
                            event.preventDefault();
                            submitAjax();
                        });

                        ajaxTarget.addEventListener('click', function (event) {
                            const link = event.target.closest('.pagination a');
                            if (!link) return;

                            event.preventDefault();
                            loadAjaxUrl(new URL(link.href, window.location.origin));
                        });

                        ajaxTarget.addEventListener('change', function (event) {
                            const perPageSelect = event.target.closest('[data-filter-per-page]');
                            if (!perPageSelect) return;

                            const perPageInput = form.querySelector('[data-filter-per-page-input]');
                            if (perPageInput) {
                                perPageInput.value = perPageSelect.value;
                            }

                            submitAjax();
                        });

                        form.querySelectorAll('[data-filter-reset]').forEach(function (resetLink) {
                            resetLink.addEventListener('click', async function (event) {
                                event.preventDefault();
                                clearFilterControls();

                                const url = new URL(resetLink.href, window.location.origin);
                                loadAjaxUrl(url);
                            });
                        });
                    }

                    form.querySelectorAll('select, input[type="date"], input[type="checkbox"], input[type="radio"]').forEach(function (field) {
                        field.addEventListener('change', submitForm);
                    });

                    form.querySelectorAll('input[name="search"], input[type="search"], input[data-filter-autosubmit="debounced"]').forEach(function (field) {
                        field.addEventListener('input', function () {
                            window.clearTimeout(filterTimer);
                            filterTimer = window.setTimeout(submitForm, delay);
                        });
                    });
                });
            });
        </script>
    @endpush
@endonce
