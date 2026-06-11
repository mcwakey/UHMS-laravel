@extends('layouts.app')
@section('title', __('pharmacy.drug_catalogue'))

@section('content')
<x-page-header :title="__('pharmacy.drug_catalogue')" :description="__('pharmacy.drug_catalogue_description')" icon="ti-pill" />

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small mb-1">{{ __('common.search') }}</label>
                <input type="text" name="search" class="form-control" placeholder="{{ __('pharmacy.search_drug_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>{{ __('common.filter') }}</button>
                <a href="{{ route('admin.pharmacy.drugs.index') }}" class="btn btn-outline-secondary btn-md">{{ __('common.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('pharmacy.col_product') }}</th>
                        <th>{{ __('pharmacy.col_product_type') }}</th>
                        <th>{{ __('pharmacy.col_unit') }}</th>
                        <th class="text-end">{{ __('pharmacy.col_pharmacy_qty') }}</th>
                        <th class="text-end">{{ __('pharmacy.col_main_stock_qty') }}</th>
                        <th class="text-end">{{ __('pharmacy.col_reorder_level') }}</th>
                        <th>{{ __('pharmacy.col_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drugs as $product)
                        @php
                            $qty = (float) ($product->available_in_pharmacy ?? 0);
                            $mainQty = (float) ($product->available_in_main_store ?? 0);
                            $reorder = (float) ($product->reorder_level ?? 0);
                            $pharmacyStatus = $product->pharmacy_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
                            $mainStatus = $product->main_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
                            $type = $product->product_type;
                            $typeLabel = $type instanceof \App\Enums\ProductType ? $type->label() : ucfirst(str_replace('_', ' ', (string) $type));
                        @endphp
                        <tr>
                            <td>
                                <span class="fw-medium">{{ $product->name }}</span>
                                @if($product->code)
                                    <br><small class="text-muted"><code>{{ $product->code }}</code></small>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark">{{ $typeLabel }}</span></td>
                            <td>{{ $product->unit ?? 'unit' }}</td>
                            <td class="text-end fw-semibold">
                                {{ rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.') }}
                                <span class="badge bg-{{ $pharmacyStatus['class'] }} ms-1">{{ $pharmacyStatus['label'] }}</span>
                            </td>
                            <td class="text-end fw-semibold">
                                {{ rtrim(rtrim(number_format($mainQty, 4, '.', ''), '0'), '.') }}
                                <span class="badge bg-{{ $mainStatus['class'] }} ms-1">{{ $mainStatus['label'] }}</span>
                            </td>
                            <td class="text-end text-muted">{{ $reorder > 0 ? rtrim(rtrim(number_format($reorder, 4, '.', ''), '0'), '.') : '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $pharmacyStatus['class'] }}">{{ $pharmacyStatus['label'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"><x-empty-state icon="ti-pill-off" :message="__('pharmacy.no_drugs_found')" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($drugs->hasPages())
        <div class="card-footer">{{ $drugs->links() }}</div>
    @endif
</div>
@endsection
