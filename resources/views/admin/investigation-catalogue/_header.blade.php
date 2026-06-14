<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center {{ $h->is_active ? '' : 'opacity-50' }}"
     data-header-id="{{ $h->id }}"
     data-name="{{ $h->name }}"
     data-description="{{ $h->description }}"
     data-sort-order="{{ $h->sort_order }}"
     data-is-active="{{ $h->is_active ? '1' : '0' }}">
    <div>
        <div class="fw-medium header-name">{{ $h->name }}</div>
        <small class="text-muted header-description {{ $h->description ? '' : 'd-none' }}">{{ $h->description }}</small>
    </div>
    <div class="d-flex gap-1 ms-2">
        <button type="button" aria-label="Edit" title="Edit" class="btn btn-xs btn-outline-primary edit-header-btn"><i class="ti ti-edit"></i></button>
        <button type="button" aria-label="Delete" title="Delete" class="btn btn-xs btn-outline-danger delete-header-btn"><i class="ti ti-trash"></i></button>
    </div>
</div>
