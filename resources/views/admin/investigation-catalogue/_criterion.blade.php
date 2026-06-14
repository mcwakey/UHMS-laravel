<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-start {{ $c->is_active ? '' : 'opacity-50' }}"
     data-criterion-id="{{ $c->id }}"
     data-header-id="{{ $c->header_id ?? '' }}"
     data-name="{{ $c->name }}"
     data-unit="{{ $c->unit }}"
     data-reference-range="{{ $c->reference_range }}"
     data-default-value="{{ $c->default_value }}"
     data-input-type="{{ $c->input_type }}"
     data-options="{{ e(json_encode($c->options ?? [])) }}"
     data-sort-order="{{ $c->sort_order }}"
     data-is-required="{{ $c->is_required ? '1' : '0' }}"
     data-is-active="{{ $c->is_active ? '1' : '0' }}">
    <div class="flex-grow-1">
        <div class="fw-medium criterion-name">{{ $c->name }}</div>
        <small class="text-muted criterion-meta">
            @if($c->unit) Unit: {{ $c->unit }} @endif
            @if($c->reference_range) &middot; Range: {{ $c->reference_range }} @endif
            @if($c->input_type) &middot; Type: {{ $c->input_type }} @endif
            @if($c->is_required) &middot; <span class="text-danger">Required</span> @endif
        </small>
    </div>
    <div class="d-flex gap-1 ms-2">
        <button type="button" aria-label="Edit" title="Edit" class="btn btn-xs btn-outline-primary edit-crit-btn"><i class="ti ti-edit"></i></button>
        <button type="button" aria-label="Delete" title="Delete" class="btn btn-xs btn-outline-danger delete-crit-btn"><i class="ti ti-trash"></i></button>
    </div>
</div>
