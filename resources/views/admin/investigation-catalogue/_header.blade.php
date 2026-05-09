<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center" data-header-id="{{ $h->id }}">
    <div>
        <div class="fw-medium">{{ $h->name }}</div>
        @if($h->description)
            <small class="text-muted">{{ $h->description }}</small>
        @endif
    </div>
    <button class="btn btn-xs btn-outline-danger delete-header-btn"><i class="ti ti-trash"></i></button>
</div>
