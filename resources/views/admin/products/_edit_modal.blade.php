<div class="modal fade" id="editProductModal-{{ $product->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.products.update', $product) }}">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">{{ __('products.edit_product', ['name' => $product->name]) }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @include('admin.products._form_fields', ['product' => $product, 'departments' => $departments, 'types' => $types])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.update') }}</button>
            </div>
        </form>
    </div>
</div>
