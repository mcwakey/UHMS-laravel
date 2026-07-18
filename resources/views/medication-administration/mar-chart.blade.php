@extends('layouts.app')
@section('title', __('medication_administration.mar_chart') . ' - ' . ($chart['header']['patient_name'] ?? __('medication_administration.patient')))

@push('styles')
<style>
    .mar-chart-table { min-width: 1080px; }
    .mar-medication-col { width: 280px; min-width: 280px; }
    .mar-time-col { width: 132px; min-width: 132px; }
    .mar-cell-btn { min-height: 86px; white-space: normal; }
    .mar-dose-cell { vertical-align: middle; }
    .mar-print-title { display: none; }
    .mar-sticky-medication { position: sticky; left: 0; z-index: 2; background: var(--bs-body-bg); }
    thead .mar-sticky-medication { z-index: 3; background: var(--bs-light); }
    @media print {
        body { background: #fff !important; }
        .sidebar, .navbar, .topbar, .app-header, .page-header, .footer, .no-print, .modal, .modal-backdrop { display: none !important; }
        .content, .container-fluid, .page-wrapper { margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
        .card { border: 1px solid #555 !important; box-shadow: none !important; break-inside: avoid; }
        .card-header, .table-light { background: #f3f3f3 !important; color: #000 !important; }
        .badge { border: 1px solid #333 !important; color: #000 !important; background: #fff !important; }
        .mar-print-title { display: block !important; text-align: center; margin-bottom: 12px; }
        .mar-chart-table { min-width: 100% !important; font-size: 11px; }
        .mar-medication-col { width: 230px !important; min-width: 230px !important; }
        .mar-time-col { width: auto !important; min-width: 86px !important; }
        .mar-cell-btn { border: 0 !important; padding: 0 !important; min-height: auto !important; }
        .mar-sticky-medication { position: static !important; }
        a[href]::after { content: '' !important; }
    }
</style>
@endpush

@section('content')
<div class="mar-print-title">
    <h3 class="mb-1">{{ strtoupper(__('medication_administration.medication_administration_record')) }}</h3>
    <div>{{ $chart['header']['patient_name'] ?? __('medication_administration.patient') }} - {{ $chart['selected_date']->format('d M Y') }}</div>
</div>

<div class="d-flex align-items-sm-center flex-sm-row flex-column justify-content-between gap-2 pb-3 mb-3 border-bottom no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="ti ti-layout-grid me-1"></i>{{ __('medication_administration.mar_chart') }}</h4>
        <p class="text-muted mb-0">{{ __('medication_administration.mar_chart_description') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('mar_chart.print')
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-printer me-1"></i>{{ __('medication_administration.print_mar') }}
        </button>
        @endcan
        @if($chart['admission'])
            <a href="{{ $workspaceRoutes->route('admin.admissions.medications.show', $chart['admission']) }}" class="btn btn-outline-primary btn-sm">{{ __('medication_administration.medication_board') }}</a>
            <a href="{{ $workspaceRoutes->route('admin.admissions.show', $chart['admission']) }}" class="btn btn-outline-secondary btn-sm">{{ __('medication_administration.admission') }}</a>
        @else
            <a href="{{ $workspaceRoutes->route('admin.emergency.medication-board') }}" class="btn btn-outline-primary btn-sm">{{ __('medication_administration.emergency_board') }}</a>
            <a href="{{ $workspaceRoutes->route('admin.visits.preview', $chart['visit']) }}" class="btn btn-outline-secondary btn-sm">{{ __('medication_administration.visit_preview') }}</a>
        @endif
    </div>
</div>

@include('medication-administration.partials.mar-chart-content', [
    'chart' => $chart,
    'stockLocations' => $stockLocations,
])
@endsection

@push('scripts')
<script>
(function () {
    function wireMedicationFormControls(root) {
        root.querySelectorAll('.med-status').forEach(function (select) {
            function update() {
                var wrap = select.closest('.modal-body') ? select.closest('.modal-body').querySelector('.med-reason-wrap') : null;
                if (wrap) wrap.classList.toggle('d-none', ['GIVEN', 'PARTIALLY_GIVEN'].includes(select.value));
            }
            select.removeEventListener('change', update);
            select.addEventListener('change', update);
            update();
        });

        root.querySelectorAll('.med-source-stock').forEach(function (select) {
            function update() {
                var wrap = select.closest('.modal-body') ? select.closest('.modal-body').querySelector('.med-stock-location-wrap') : null;
                if (wrap) wrap.classList.toggle('d-none', select.value === 'PATIENT_DISPENSED_STOCK');
            }
            select.removeEventListener('change', update);
            select.addEventListener('change', update);
            update();
        });
    }

    function cleanupModalBackdrop() {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) { backdrop.remove(); });
    }

    function loadMarChart(url, pushState) {
        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Mar-Partial': 'chart'
            }
        })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                var current = document.getElementById('mar-chart-content');
                if (!current) return;
                current.outerHTML = html;
                if (pushState) window.history.pushState({}, '', url);
                wireMedicationFormControls(document);
            });
    }

    function showErrors(form, data) {
        var errorBox = form.querySelector('.js-med-admin-errors');
        if (!errorBox) return;

        var messages = [];
        if (data && data.errors) {
            Object.keys(data.errors).forEach(function (key) {
                messages = messages.concat(data.errors[key]);
            });
        } else if (data && data.message) {
            messages.push(data.message);
        } else {
            messages.push(@json(__('medication_administration.unable_to_save_administration')));
        }

        errorBox.innerHTML = messages.map(function (message) { return '<div>' + message + '</div>'; }).join('');
        errorBox.classList.remove('d-none');
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('.js-med-admin-form');
        if (!form) return;

        event.preventDefault();
        var errorBox = form.querySelector('.js-med-admin-errors');
        if (errorBox) {
            errorBox.classList.add('d-none');
            errorBox.innerHTML = '';
        }

        fetch(form.action, {
            method: form.method || 'POST',
            body: new FormData(form),
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (response.ok) return response.json();
                return response.json().then(function (data) { throw data; });
            })
            .then(function () {
                var modalElement = form.closest('.modal');
                if (modalElement && window.bootstrap) {
                    var modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                    modal.hide();
                }
                cleanupModalBackdrop();
                return loadMarChart(window.location.href, false);
            })
            .catch(function (data) {
                showErrors(form, data);
            });
    });

    document.addEventListener('click', function (event) {
        var link = event.target.closest('.js-mar-date-link');
        if (!link) return;

        event.preventDefault();
        loadMarChart(link.href, true);
    });

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('#mar-date-form');
        if (!form) return;

        event.preventDefault();
        var date = form.querySelector('[name="date"]').value;
        var url = new URL(form.action, window.location.origin);
        url.searchParams.set('date', date);
        loadMarChart(url.toString(), true);
    });

    window.addEventListener('popstate', function () {
        loadMarChart(window.location.href, false);
    });

    wireMedicationFormControls(document);
})();
</script>
@endpush