@extends('layouts.app')
@section('title', __('invoices.counter_sale'))

@php $genders = ['Male', 'Female', 'Other']; @endphp

@section('content')
<x-page-header title="{{ __('invoices.counter_sale') }}" description="{{ __('invoices.counter_sale_description') }}" icon="ti-cash-register">
    <x-slot:actions>
        <a href="{{ route('admin.billing.invoices.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>{{ __('invoices.title') }}</a>
    </x-slot:actions>
</x-page-header>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<ul class="nav nav-tabs mb-3" id="counterSaleTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPharmacy" type="button"><i class="ti ti-pill me-1"></i>{{ __('invoices.pharmacy_sale_tab') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabInvestigation" type="button"><i class="ti ti-microscope me-1"></i>{{ __('invoices.investigation_sale_tab') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabProcedure" type="button"><i class="ti ti-stethoscope me-1"></i>{{ __('invoices.procedure_sale_tab') }}</button></li>
</ul>

<div class="tab-content">
    {{-- ========================= PHARMACY (DRUGS) ========================= --}}
    <div class="tab-pane fade show active" id="tabPharmacy">
        <form method="POST" action="{{ route('admin.billing.counter-sale.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-user me-1"></i>{{ __('invoices.walk_in_recipient') }}</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-5"><label class="form-label">{{ __('common.name') }} <span class="text-danger">*</span></label><input name="external_party_name" class="form-control" required></div>
                                <div class="col-md-3"><label class="form-label">{{ __('common.gender') }}</label><select name="external_party_sex" class="form-select"><option value="">—</option>@foreach($genders as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach</select></div>
                                <div class="col-md-2"><label class="form-label">{{ __('common.age') }}</label><input type="number" name="external_party_age" min="0" max="150" class="form-control"></div>
                                <div class="col-md-2"><label class="form-label">{{ __('invoices.contact_label') }}</label><input name="external_party_contact" class="form-control"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0"><i class="ti ti-pill me-1"></i>{{ __('invoices.drugs_section') }} <span class="text-muted small">({{ __('invoices.drugs_stock_note') }})</span></h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row data-tpl="drugRowTpl" data-body="drugRows"><i class="ti ti-plus me-1"></i>{{ __('invoices.add_drug_btn') }}</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light"><tr><th>{{ __('common.drug') }}</th><th style="width:110px">{{ __('common.qty') }}</th><th style="width:120px" class="text-end">{{ __('invoices.price') }}</th><th style="width:130px" class="text-end">{{ __('invoices.total_col') }}</th><th style="width:40px"></th></tr></thead>
                                    <tbody id="drugRows"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card position-sticky" style="top:1rem">
                        <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-receipt me-1"></i>{{ __('invoices.pharmacy_sale_tab') }}</h6></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3"><span class="fw-semibold">{{ __('invoices.total') }}</span><span class="fw-bold fs-5" data-total="drugRows">₵0.00</span></div>
                            <button type="submit" class="btn btn-primary w-100"><i class="ti ti-device-floppy me-1"></i>{{ __('invoices.create_pharmacy_sale_btn') }}</button>
                            <p class="small text-muted mt-2 mb-0">{{ __('invoices.pharmacy_sale_note') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ====================== INVESTIGATIONS (LAB) ====================== --}}
    <div class="tab-pane fade" id="tabInvestigation">
        <form method="POST" action="{{ route('admin.billing.counter-sale.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-user me-1"></i>{{ __('invoices.walk_in_recipient') }}</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-5"><label class="form-label">{{ __('common.name') }} <span class="text-danger">*</span></label><input name="external_party_name" class="form-control" required></div>
                                <div class="col-md-3"><label class="form-label">{{ __('common.gender') }}</label><select name="external_party_sex" class="form-select"><option value="">—</option>@foreach($genders as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach</select></div>
                                <div class="col-md-2"><label class="form-label">{{ __('common.age') }}</label><input type="number" name="external_party_age" min="0" max="150" class="form-control"></div>
                                <div class="col-md-2"><label class="form-label">{{ __('invoices.contact_label') }}</label><input name="external_party_contact" class="form-control"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0"><i class="ti ti-microscope me-1"></i>{{ __('invoices.investigations_section') }} <span class="text-muted small">({{ __('invoices.investigations_lab_note') }})</span></h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row data-tpl="serviceRowTpl" data-body="serviceRows"><i class="ti ti-plus me-1"></i>{{ __('invoices.add_investigation_btn') }}</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light"><tr><th>{{ __('invoices.investigation_col') }}</th><th style="width:110px">{{ __('common.qty') }}</th><th style="width:120px" class="text-end">{{ __('invoices.price') }}</th><th style="width:130px" class="text-end">{{ __('invoices.total_col') }}</th><th style="width:40px"></th></tr></thead>
                                    <tbody id="serviceRows"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card position-sticky" style="top:1rem">
                        <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-receipt me-1"></i>{{ __('invoices.investigation_sale_tab') }}</h6></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3"><span class="fw-semibold">{{ __('invoices.total') }}</span><span class="fw-bold fs-5" data-total="serviceRows">₵0.00</span></div>
                            <button type="submit" class="btn btn-primary w-100"><i class="ti ti-device-floppy me-1"></i>{{ __('invoices.create_investigation_sale_btn') }}</button>
                            <p class="small text-muted mt-2 mb-0">{{ __('invoices.investigation_sale_note') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ========================= PROCEDURES ========================= --}}
    <div class="tab-pane fade" id="tabProcedure">
        <form method="POST" action="{{ route('admin.billing.counter-sale.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-user me-1"></i>{{ __('invoices.walk_in_recipient') }}</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-5"><label class="form-label">{{ __('common.name') }} <span class="text-danger">*</span></label><input name="external_party_name" class="form-control" required></div>
                                <div class="col-md-3"><label class="form-label">{{ __('common.gender') }}</label><select name="external_party_sex" class="form-select"><option value="">—</option>@foreach($genders as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach</select></div>
                                <div class="col-md-2"><label class="form-label">{{ __('common.age') }}</label><input type="number" name="external_party_age" min="0" max="150" class="form-control"></div>
                                <div class="col-md-2"><label class="form-label">{{ __('invoices.contact_label') }}</label><input name="external_party_contact" class="form-control"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('invoices.procedures_section') }}</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-row data-tpl="procedureRowTpl" data-body="procedureRows"><i class="ti ti-plus me-1"></i>{{ __('invoices.add_procedure_btn') }}</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light"><tr><th>{{ __('invoices.procedure_col') }}</th><th style="width:110px">{{ __('common.qty') }}</th><th style="width:120px" class="text-end">{{ __('invoices.price') }}</th><th style="width:130px" class="text-end">{{ __('invoices.total_col') }}</th><th style="width:40px"></th></tr></thead>
                                    <tbody id="procedureRows"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card position-sticky" style="top:1rem">
                        <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-receipt me-1"></i>{{ __('invoices.procedure_sale_tab') }}</h6></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3"><span class="fw-semibold">{{ __('invoices.total') }}</span><span class="fw-bold fs-5" data-total="procedureRows">₵0.00</span></div>
                            <button type="submit" class="btn btn-primary w-100"><i class="ti ti-device-floppy me-1"></i>{{ __('invoices.create_procedure_sale_btn') }}</button>
                            <p class="small text-muted mt-2 mb-0">{{ __('invoices.procedure_sale_note') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Row templates --}}
<template id="drugRowTpl">
    <tr class="sale-row">
        <td><select name="drug_id[]" class="form-select row-pick" data-search="{{ route('admin.billing.counter-sale.drug-search') }}" style="width:100%"></select></td>
        <td><input type="number" name="drug_qty[]" class="form-control row-qty" value="1" min="1"></td>
        <td class="text-end row-price">₵0.00</td>
        <td class="text-end row-line">₵0.00</td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger row-remove p-0"><i class="ti ti-trash"></i></button></td>
    </tr>
</template>
<template id="serviceRowTpl">
    <tr class="sale-row">
        <td><select name="service_id[]" class="form-select row-pick" data-search="{{ route('admin.billing.counter-sale.service-search') }}" style="width:100%"></select></td>
        <td><input type="number" name="service_qty[]" class="form-control row-qty" value="1" min="1"></td>
        <td class="text-end row-price">₵0.00</td>
        <td class="text-end row-line">₵0.00</td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger row-remove p-0"><i class="ti ti-trash"></i></button></td>
    </tr>
</template>
<template id="procedureRowTpl">
    <tr class="sale-row">
        <td><select name="procedure_id[]" class="form-select row-pick" data-search="{{ route('admin.billing.counter-sale.procedure-search') }}" style="width:100%"></select></td>
        <td><input type="number" name="procedure_qty[]" class="form-control row-qty" value="1" min="1"></td>
        <td class="text-end row-price">₵0.00</td>
        <td class="text-end row-line">₵0.00</td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger row-remove p-0"><i class="ti ti-trash"></i></button></td>
    </tr>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    function money(n){ return '₵' + (Math.round(n*100)/100).toFixed(2); }

    function recalcBody(bodyId) {
        var total = 0;
        document.querySelectorAll('#' + bodyId + ' .sale-row').forEach(function (row) {
            var price = parseFloat(row.dataset.price || 0);
            var qty = parseInt(row.querySelector('.row-qty').value || 1, 10);
            var line = price * qty;
            row.querySelector('.row-price').textContent = money(price);
            row.querySelector('.row-line').textContent = money(line);
            total += line;
        });
        var el = document.querySelector('[data-total="' + bodyId + '"]');
        if (el) el.textContent = money(total);
    }

    function initPick(select, bodyId) {
        if (!$ || !$.fn.select2) return;
        $(select).select2({
            width: '100%', placeholder: 'Search…', minimumInputLength: 1,
            ajax: {
                url: select.dataset.search, dataType: 'json', delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data }; },
                cache: true
            }
        }).on('select2:select', function (e) {
            select.closest('tr').dataset.price = e.params.data.price || 0;
            recalcBody(bodyId);
        });
    }

    function addRow(tplId, bodyId) {
        var clone = document.getElementById(tplId).content.firstElementChild.cloneNode(true);
        document.getElementById(bodyId).appendChild(clone);
        initPick(clone.querySelector('.row-pick'), bodyId);
        clone.querySelector('.row-qty').addEventListener('input', function () { recalcBody(bodyId); });
        clone.querySelector('.row-remove').addEventListener('click', function () { clone.remove(); recalcBody(bodyId); });
        recalcBody(bodyId);
    }

    document.querySelectorAll('[data-add-row]').forEach(function (btn) {
        btn.addEventListener('click', function () { addRow(btn.dataset.tpl, btn.dataset.body); });
    });

    // Seed the active tab; seed the other tab's first row only once it becomes visible
    // (select2 needs a visible container to size correctly).
    addRow('drugRowTpl', 'drugRows');
    var tabSeed = {
        '#tabInvestigation': { body: 'serviceRows', tpl: 'serviceRowTpl' },
        '#tabProcedure': { body: 'procedureRows', tpl: 'procedureRowTpl' },
        '#tabPharmacy': { body: 'drugRows', tpl: 'drugRowTpl' },
    };
    document.querySelectorAll('#counterSaleTabs button').forEach(function (tabBtn) {
        tabBtn.addEventListener('shown.bs.tab', function () {
            var seed = tabSeed[tabBtn.dataset.bsTarget] || tabSeed['#tabPharmacy'];
            if (!document.querySelector('#' + seed.body + ' .sale-row')) {
                addRow(seed.tpl, seed.body);
            }
        });
    });
});
</script>
@endpush
@endsection
