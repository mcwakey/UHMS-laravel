{{--
    Reusable "Consumables Used" widget for investigation result entry.
    Variables expected:
        $item        — LabRequestItem
    Optional:
        $defaultConsumables — Collection of service consumables (auto-loaded if missing)
--}}
@php
    /** @var \App\Models\LabRequestItem $item */
    $defaults = $defaultConsumables
        ?? ($item->service
            ? app(\App\Services\ConsumableUsageService::class)->defaultsForService($item->service->id)
            : collect());
@endphp

<details class="mb-3 border rounded p-2">
    <summary class="fw-medium small text-muted text-uppercase">
        <i class="ti ti-package me-1"></i> {{ __('lab.consumables_summary') }}
    </summary>
    <div class="mt-2">
        <div class="table-responsive"><table class="table table-sm align-middle mb-1" id="consumablesTable-{{ $item->id }}">
            <thead>
                <tr>
                    <th style="width:55%;">{{ __('lab.product_col') }}</th>
                    <th style="width:20%;">{{ __('lab.qty_col') }}</th>
                    <th style="width:20%;">{{ __('lab.notes_col') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($defaults as $i => $d)
                <tr class="consumable-row">
                    <td>
                        <select name="consumables[{{ $i }}][product_id]" class="form-select form-select-sm">
                            <option value="">— skip —</option>
                            <option value="{{ $d->product_id }}" selected>{{ $d->product->name ?? '' }}</option>
                        </select>
                    </td>
                    <td><input type="number" step="0.0001" min="0" name="consumables[{{ $i }}][quantity]" class="form-control form-control-sm" value="{{ $d->default_quantity }}"></td>
                    <td><input name="consumables[{{ $i }}][notes]" class="form-control form-control-sm" maxlength="255"></td>
                    <td class="text-end"><button type="button" class="btn btn-link btn-sm text-danger remove-consumable">×</button></td>
                </tr>
            @endforeach
            @if($defaults->isEmpty())
                <tr class="consumable-row">
                    <td>
                        <input list="products-datalist-{{ $item->id }}" name="consumables[0][product_id]" class="form-control form-control-sm" placeholder="{{ __('lab.search_product_placeholder') }}">
                    </td>
                    <td><input type="number" step="0.0001" min="0" name="consumables[0][quantity]" class="form-control form-control-sm"></td>
                    <td><input name="consumables[0][notes]" class="form-control form-control-sm" maxlength="255"></td>
                    <td class="text-end"><button type="button" class="btn btn-link btn-sm text-danger remove-consumable">×</button></td>
                </tr>
            @endif
            </tbody>
        </table></div>
        <div class="form-text">{{ __('lab.consumables_blank_hint') }}</div>
    </div>
</details>
