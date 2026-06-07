@extends('layouts.app')
@section('title', 'Counter Sale')

@section('content')
<x-page-header title="Counter Sale" description="Walk-in cash sale for drugs and investigations — no visit required." icon="ti-cash-register">
    <x-slot:actions>
        <a href="{{ route('admin.billing.invoices.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Invoices</a>
    </x-slot:actions>
</x-page-header>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('admin.billing.counter-sale.store') }}" id="counterSaleForm">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Recipient --}}
            <div class="card mb-3">
                <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-user me-1"></i>Walk-in Recipient</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7"><label class="form-label">Name <span class="text-danger">*</span></label><input name="external_party_name" class="form-control" value="{{ old('external_party_name') }}" required></div>
                        <div class="col-md-5"><label class="form-label">Contact</label><input name="external_party_contact" class="form-control" value="{{ old('external_party_contact') }}"></div>
                    </div>
                </div>
            </div>

            {{-- Drugs --}}
            <div class="card mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0"><i class="ti ti-pill me-1"></i>Drugs <span class="text-muted small">(decrements pharmacy stock)</span></h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addDrugRow"><i class="ti ti-plus me-1"></i>Add Drug</button>
                </div>
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light"><tr><th>Drug</th><th style="width:110px">Qty</th><th style="width:120px" class="text-end">Price</th><th style="width:130px" class="text-end">Line</th><th style="width:40px"></th></tr></thead>
                        <tbody id="drugRows"></tbody>
                    </table>
                </div>
            </div>

            {{-- Investigations --}}
            <div class="card mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0"><i class="ti ti-microscope me-1"></i>Investigations <span class="text-muted small">(raises a lab request)</span></h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addServiceRow"><i class="ti ti-plus me-1"></i>Add Investigation</button>
                </div>
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light"><tr><th>Investigation</th><th style="width:110px">Qty</th><th style="width:120px" class="text-end">Price</th><th style="width:130px" class="text-end">Line</th><th style="width:40px"></th></tr></thead>
                        <tbody id="serviceRows"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Summary --}}
        <div class="col-lg-4">
            <div class="card position-sticky" style="top:1rem">
                <div class="card-header bg-white"><h6 class="card-title mb-0"><i class="ti ti-receipt me-1"></i>Sale Summary</h6></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Drugs</span><span id="sumDrugs">₵0.00</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Investigations</span><span id="sumServices">₵0.00</span></div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3"><span class="fw-semibold">Total</span><span class="fw-bold fs-5" id="sumTotal">₵0.00</span></div>
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-device-floppy me-1"></i>Create Sale &amp; Go to Payment</button>
                    <p class="small text-muted mt-2 mb-0">A standalone cash invoice is raised; collect payment and print the receipt on the next screen. Drug stock is decremented on save.</p>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Row templates --}}
<template id="drugRowTpl">
    <tr class="sale-row" data-kind="drug">
        <td><select name="drug_id[]" class="form-select row-pick" data-search="{{ route('admin.billing.counter-sale.drug-search') }}" style="width:100%"></select></td>
        <td><input type="number" name="drug_qty[]" class="form-control row-qty" value="1" min="1"></td>
        <td class="text-end row-price">₵0.00</td>
        <td class="text-end row-line">₵0.00</td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger row-remove p-0"><i class="ti ti-trash"></i></button></td>
    </tr>
</template>
<template id="serviceRowTpl">
    <tr class="sale-row" data-kind="service">
        <td><select name="service_id[]" class="form-select row-pick" data-search="{{ route('admin.billing.counter-sale.service-search') }}" style="width:100%"></select></td>
        <td><input type="number" name="service_qty[]" class="form-control row-qty" value="1" min="1"></td>
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

    function initPick(select) {
        if (!$ || !$.fn.select2) return;
        $(select).select2({
            width: '100%',
            placeholder: 'Search…',
            minimumInputLength: 1,
            ajax: {
                url: select.dataset.search,
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data }; },
                cache: true
            }
        }).on('select2:select', function (e) {
            var row = select.closest('tr');
            row.dataset.price = e.params.data.price || 0;
            recalc();
        });
    }

    function recalc() {
        var sums = { drug: 0, service: 0 };
        document.querySelectorAll('.sale-row').forEach(function (row) {
            var price = parseFloat(row.dataset.price || 0);
            var qty = parseInt(row.querySelector('.row-qty').value || 1, 10);
            var line = price * qty;
            row.querySelector('.row-price').textContent = money(price);
            row.querySelector('.row-line').textContent = money(line);
            sums[row.dataset.kind] += line;
        });
        document.getElementById('sumDrugs').textContent = money(sums.drug);
        document.getElementById('sumServices').textContent = money(sums.service);
        document.getElementById('sumTotal').textContent = money(sums.drug + sums.service);
    }

    function addRow(tplId, bodyId) {
        var tpl = document.getElementById(tplId);
        var clone = tpl.content.firstElementChild.cloneNode(true);
        document.getElementById(bodyId).appendChild(clone);
        initPick(clone.querySelector('.row-pick'));
        clone.querySelector('.row-qty').addEventListener('input', recalc);
        clone.querySelector('.row-remove').addEventListener('click', function () { clone.remove(); recalc(); });
        recalc();
    }

    document.getElementById('addDrugRow').addEventListener('click', function () { addRow('drugRowTpl', 'drugRows'); });
    document.getElementById('addServiceRow').addEventListener('click', function () { addRow('serviceRowTpl', 'serviceRows'); });

    // Start with one of each.
    addRow('drugRowTpl', 'drugRows');
    addRow('serviceRowTpl', 'serviceRows');
});
</script>
@endpush
@endsection
