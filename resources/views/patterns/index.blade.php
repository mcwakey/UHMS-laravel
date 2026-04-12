@extends('layouts.app')
@section('title', 'Medical Patterns')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0"><i class="ti ti-template me-2"></i>Medical Patterns</h4>
        @can('consultations.create')
        <a href="{{ route('admin.patterns.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>New Pattern
        </a>
        @endcan
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search patterns..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="scope" class="form-select">
                    <option value="">All Patterns</option>
                    <option value="mine" {{ request('scope') === 'mine' ? 'selected' : '' }}>My Patterns</option>
                    <option value="system" {{ request('scope') === 'system' ? 'selected' : '' }}>System-Wide</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="inactive" id="inactiveFilter" {{ request('inactive') ? 'checked' : '' }}>
                    <label class="form-check-label" for="inactiveFilter">Show Inactive Only</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="ti ti-filter me-1"></i>Filter</button>
            </div>
        </form>

        <!-- Patterns Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Pattern Name</th>
                        <th>Scope</th>
                        <th>Items</th>
                        <th>Usage</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patterns as $pattern)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $pattern->name }}</div>
                            <small class="text-muted">
                                @foreach($pattern->items->groupBy('type') as $type => $items)
                                    <span class="badge bg-{{ $items->first()->getTypeColor() }}-subtle text-{{ $items->first()->getTypeColor() }} me-1">
                                        {{ $items->count() }} {{ $items->first()->getTypeLabel() }}{{ $items->count() > 1 ? 's' : '' }}
                                    </span>
                                @endforeach
                            </small>
                        </td>
                        <td>
                            @if($pattern->is_system)
                                <span class="badge bg-primary-subtle text-primary">System</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">
                                    {{ $pattern->doctor?->full_name ?? 'Personal' }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $pattern->items->count() }}</td>
                        <td>
                            <span class="fw-medium">{{ $pattern->usage_count }}</span>
                            <small class="text-muted">times</small>
                        </td>
                        <td>
                            @if($pattern->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $pattern->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-info view-pattern-btn"
                                        data-pattern-id="{{ $pattern->id }}"
                                        data-bs-toggle="modal" data-bs-target="#patternDetailModal">
                                    <i class="ti ti-eye"></i>
                                </button>
                                @can('consultations.create')
                                <form method="POST" action="{{ route('admin.patterns.toggle', $pattern) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $pattern->is_active ? 'warning' : 'success' }}" title="{{ $pattern->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="ti ti-{{ $pattern->is_active ? 'player-pause' : 'player-play' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.patterns.destroy', $pattern) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete pattern &quot;{{ $pattern->name }}&quot;?')">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="ti ti-template fs-1 d-block mb-2"></i>
                            No patterns found. <a href="{{ route('admin.patterns.create') }}">Create one</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $patterns->withQueryString()->links() }}
    </div>
</div>

<!-- Pattern Detail Modal -->
<div class="modal fade" id="patternDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-template me-2"></i>Pattern Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="patternDetailContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading pattern...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.view-pattern-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var patternId = this.dataset.patternId;
        var content = document.getElementById('patternDetailContent');
        content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading pattern...</p></div>';

        fetch('{{ url("admin/patterns") }}/' + patternId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var p = data.pattern;
            var html = '<h5 class="fw-bold mb-1">' + escapeHtml(p.name) + '</h5>';
            html += '<p class="text-muted mb-3">' + (p.doctor_id ? 'Personal pattern' : 'System-wide pattern') + ' &middot; Used ' + p.usage_count + ' times</p>';

            var types = ['complaint', 'diagnosis', 'treatment', 'prescription_item'];
            var labels = { complaint: 'Complaints', diagnosis: 'Diagnoses', treatment: 'Treatments', prescription_item: 'Prescription Items' };
            var colors = { complaint: 'warning', diagnosis: 'info', treatment: 'success', prescription_item: 'primary' };

            types.forEach(function(type) {
                var items = p.items.filter(function(i) { return i.type === type; });
                if (items.length > 0) {
                    html += '<h6 class="fw-bold mt-3"><span class="badge bg-' + colors[type] + ' me-1">' + items.length + '</span>' + labels[type] + '</h6>';
                    html += '<ul class="list-group list-group-flush">';
                    items.forEach(function(item) {
                        var d = typeof item.data === 'string' ? JSON.parse(item.data) : item.data;
                        html += '<li class="list-group-item">';
                        if (type === 'complaint') {
                            html += '<strong>' + escapeHtml(d.description || '') + '</strong>';
                            if (d.duration) html += ' <small class="text-muted">(' + escapeHtml(d.duration) + ')</small>';
                            if (d.severity) html += ' <span class="badge bg-' + (d.severity === 'severe' ? 'danger' : (d.severity === 'moderate' ? 'warning' : 'info')) + '">' + escapeHtml(d.severity) + '</span>';
                        } else if (type === 'diagnosis') {
                            html += '<strong>' + escapeHtml(d.description || '') + '</strong>';
                            if (d.icd_code) html += ' <code>' + escapeHtml(d.icd_code) + '</code>';
                            if (d.type) html += ' <span class="badge bg-secondary">' + escapeHtml(d.type) + '</span>';
                        } else if (type === 'treatment') {
                            html += '<span class="badge bg-secondary me-1">' + escapeHtml(d.type || '') + '</span>';
                            html += escapeHtml(d.description || '');
                        } else if (type === 'prescription_item') {
                            html += '<strong>' + escapeHtml(d.drug_name || '') + '</strong> ';
                            html += escapeHtml(d.dosage || '') + ' ' + escapeHtml(d.frequency || '') + ' x ' + escapeHtml(d.duration || '');
                            html += ' (Qty: ' + escapeHtml(String(d.quantity || '')) + ', ' + escapeHtml(d.route || 'oral') + ')';
                        }
                        html += '</li>';
                    });
                    html += '</ul>';
                }
            });

            content.innerHTML = html;
        })
        .catch(function() {
            content.innerHTML = '<div class="alert alert-danger">Failed to load pattern details.</div>';
        });
    });
});

function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>
@endpush
