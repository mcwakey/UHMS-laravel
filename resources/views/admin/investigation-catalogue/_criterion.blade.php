<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-start" data-criterion-id="{{ $c->id }}" data-header-id="{{ $c->header_id ?? '' }}">
    <div class="flex-grow-1">
        <div class="fw-medium">{{ $c->name }}</div>
        <small class="text-muted">
            @if($c->unit) Unit: {{ $c->unit }} @endif
            @if($c->reference_range) &middot; Range: {{ $c->reference_range }} @endif
            @if($c->input_type) &middot; Type: {{ $c->input_type }} @endif
            @if($c->is_required) &middot; <span class="text-danger">Required</span> @endif
        </small>
    </div>
    <button aria-label="Delete" title="Delete" class="btn btn-xs btn-outline-danger delete-crit-btn ms-2"><i class="ti ti-trash"></i></button>
</div>
