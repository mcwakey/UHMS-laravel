{{--
    Render the consumables picker for a stage.
    Inputs:
      $defaultConsumables — collection (or empty) of ServiceConsumable with ->product
      $stage (string) — used only for unique row ids
--}}
@php $stageId = strtolower($stage ?? 'stage'); @endphp
<hr>
<h6 class="text-uppercase text-muted small mb-2">Consumables Used</h6>
<div class="table-responsive">
    <table class="table table-sm align-middle mb-1" id="consumables-{{ $stageId }}">
        <thead class="table-light">
            <tr><th style="width:55%">Product</th><th style="width:15%">Qty</th><th>Notes</th><th style="width:32px"></th></tr>
        </thead>
        <tbody>
            @forelse(($defaultConsumables ?? collect()) as $i => $sc)
                <tr>
                    <td>
                        <input type="hidden" name="consumables[{{ $i }}][product_id]" value="{{ $sc->product_id }}">
                        {{ $sc->product->name }}
                        @if($sc->product->unit)<small class="text-muted">— {{ $sc->product->unit }}</small>@endif
                        @if($sc->is_required)<span class="badge bg-warning-subtle text-warning ms-1">Required</span>@endif
                    </td>
                    <td><input type="number" step="0.0001" min="0" name="consumables[{{ $i }}][quantity]" value="{{ $sc->default_quantity }}" class="form-control form-control-sm"></td>
                    <td><input type="text" name="consumables[{{ $i }}][notes]" class="form-control form-control-sm"></td>
                    <td class="text-end">
                        <button aria-label="Close" title="Close" type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove();"><i class="ti ti-x"></i></button>
                    </td>
                </tr>
            @empty
            @endforelse
        </tbody>
    </table>
    <small class="text-muted d-block">Set quantity to 0 (or remove the row) to skip a consumable. Stock will be deducted on save.</small>
</div>
