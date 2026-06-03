{{--
    Insurance Prices modal for a Product — visually identical to the
    services index pricesModal. Single form POST -> admin.products.pricing.store
    Required vars: $product, $insuranceTypes, $insuranceProviders
--}}
@php
    $typeDefaults   = $product->prices->whereNull('insurance_provider_id')->keyBy('insurance_type');
    $providerPrices = $product->prices->whereNotNull('insurance_provider_id')->values();
    $basePrice      = (float) ($product->base_price ?? 0);
@endphp

<div class="modal fade" id="productPricesModal-{{ $product->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="ti ti-tag me-1"></i>Insurance Prices — {{ $product->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="{{ route('admin.products.pricing.store', $product) }}">
                @csrf
                <div class="modal-body">

                    {{-- Base price + Billable toggle (inline, same form) --}}
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Base Price (cash)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">&#8373;</span>
                                <input type="number" form="basePriceForm-{{ $product->id }}"
                                       name="base_price" class="form-control"
                                       value="{{ number_format($basePrice, 2, '.', '') }}"
                                       step="0.01" min="0">
                            </div>
                            <small class="text-muted">Fallback when no insurance-specific price is set.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small d-block">Billable?</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       form="basePriceForm-{{ $product->id }}"
                                       name="is_billable" value="1"
                                       id="isBillable-{{ $product->id }}"
                                       @checked($product->is_billable)>
                                <label class="form-check-label" for="isBillable-{{ $product->id }}">
                                    Include in invoices
                                </label>
                            </div>
                            <small class="text-muted">Uncheck for non-chargeable supplies.</small>
                        </div>
                        <div class="col-12">
                            <button type="submit" form="basePriceForm-{{ $product->id }}"
                                    class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-device-floppy me-1"></i>Update Base Price
                            </button>
                        </div>
                    </div>

                    {{-- Default prices per type --}}
                    <h6 class="fw-bold mb-1">Default Prices by Insurance Type</h6>
                    <p class="text-muted small mb-3">
                        Applied to all patients with that insurance type (overrides base price).
                        Leave blank to use base price (&#8373;{{ number_format($basePrice, 2) }}).
                    </p>
                    <div class="row g-3 mb-4">
                        @foreach($insuranceTypes as $type)
                            @php $existing = $typeDefaults[$type->value] ?? null; @endphp
                            <div class="col-md-3">
                                <label class="form-label fw-medium">
                                    <x-status-badge :status="$type" />
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8373;</span>
                                    <input type="number" name="type_prices[{{ $type->value }}]"
                                           class="form-control"
                                           value="{{ $existing ? number_format($existing->price, 2, '.', '') : '' }}"
                                           placeholder="{{ number_format($basePrice, 2) }}"
                                           step="0.01" min="0">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Provider-specific overrides --}}
                    <h6 class="fw-bold mb-1">Provider-Specific Overrides</h6>
                    <p class="text-muted small mb-3">
                        Negotiated rates for specific insurance companies. These override the type default above.
                    </p>
                    <div id="productProviderPrices-{{ $product->id }}">
                        @foreach($providerPrices as $idx => $pp)
                            <div class="row g-2 align-items-end mb-2 provider-price-row">
                                <div class="col-md-4">
                                    <label class="form-label small">Type</label>
                                    <select name="provider_prices[{{ $idx }}][insurance_type]"
                                            class="form-select form-select-sm type-select" required>
                                        @foreach($insuranceTypes as $type)
                                            <option value="{{ $type->value }}" {{ $pp->insurance_type === $type->value ? 'selected' : '' }}>
                                                {{ $type->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Provider</label>
                                    <select name="provider_prices[{{ $idx }}][insurance_provider_id]"
                                            class="form-select form-select-sm provider-select" required>
                                        @foreach($insuranceProviders as $prov)
                                            <option value="{{ $prov->id }}" data-type="{{ $prov->type }}" {{ $pp->insurance_provider_id == $prov->id ? 'selected' : '' }}>
                                                {{ $prov->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Price (&#8373;)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">&#8373;</span>
                                        <input type="number" name="provider_prices[{{ $idx }}][price]"
                                               class="form-control"
                                               value="{{ number_format($pp->price, 2, '.', '') }}"
                                               step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex align-items-end pb-1">
                                    <a href="#"
                                       onclick="event.preventDefault(); if(confirm('Remove this price?')){ document.getElementById('delPrice-{{ $product->id }}-{{ $pp->id }}').submit(); }"
                                       class="btn btn-sm btn-outline-danger" aria-label="Delete" title="Delete">
                                        <i class="ti ti-trash"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1 add-provider-row"
                            data-target="productProviderPrices-{{ $product->id }}"
                            data-types='@json(collect($insuranceTypes)->map(fn($t)=>["value"=>$t->value,"label"=>$t->label()]))'
                            data-providers='@json($insuranceProviders->map(fn($p)=>["id"=>$p->id,"name"=>$p->name,"type"=>$p->type]))'>
                        <i class="ti ti-plus me-1"></i>Add Provider Override
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Save Insurance Prices
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Out-of-modal helper forms (modals must not nest forms) --}}
<form id="basePriceForm-{{ $product->id }}" method="POST"
      action="{{ route('admin.products.pricing.base.update', $product) }}" class="d-none">
    @csrf @method('PATCH')
</form>

@foreach($providerPrices as $pp)
    <form id="delPrice-{{ $product->id }}-{{ $pp->id }}" method="POST"
          action="{{ route('admin.products.pricing.delete', [$product, $pp]) }}" class="d-none">
        @csrf @method('DELETE')
    </form>
@endforeach
@foreach($typeDefaults as $tp)
    <form id="delPrice-{{ $product->id }}-{{ $tp->id }}" method="POST"
          action="{{ route('admin.products.pricing.delete', [$product, $tp]) }}" class="d-none">
        @csrf @method('DELETE')
    </form>
@endforeach
