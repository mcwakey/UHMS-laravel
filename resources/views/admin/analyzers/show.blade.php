@extends('layouts.app')
@section('title', $analyzer->name . ' — Analyzer Detail')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <a href="{{ route('admin.analyzers.index') }}" class="text-muted text-decoration-none">
                <i class="ti ti-device-analytics me-1"></i>Analyzers
            </a>
            <i class="ti ti-chevron-right mx-1 fs-6 text-muted"></i>
            {{ $analyzer->name }}
        </h4>
        @if($analyzer->manufacturer || $analyzer->model)
        <small class="text-muted">{{ $analyzer->manufacturer }} {{ $analyzer->model }}</small>
        @endif
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-{{ $analyzer->status_color }} fs-6 px-3 py-2">
            {{ $analyzer->is_active ? 'Active' : 'Inactive' }}
        </span>
        <span class="badge bg-{{ $analyzer->protocol === 'hl7' ? 'info' : 'warning' }} fs-6 px-3 py-2">
            {{ $analyzer->protocol_label }}
        </span>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">
    <!-- Device Info -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="ti ti-info-circle me-2"></i>Device Information</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th class="w-50">Name</th><td>{{ $analyzer->name }}</td></tr>
                    <tr><th>Manufacturer</th><td>{{ $analyzer->manufacturer ?: '—' }}</td></tr>
                    <tr><th>Model</th><td>{{ $analyzer->model ?: '—' }}</td></tr>
                    <tr><th>Protocol</th><td>{{ $analyzer->protocol_label }}</td></tr>
                    <tr>
                        <th>Connection</th>
                        <td>
                            <i class="ti ti-{{ $analyzer->connection_type === 'tcp' ? 'network' : 'usb' }} me-1"></i>
                            {{ $analyzer->connection_info }}
                        </td>
                    </tr>
                    <tr>
                        <th>Last Connected</th>
                        <td>
                            @if($analyzer->last_connected_at)
                            {{ $analyzer->last_connected_at->format('d M Y H:i') }}
                            <br><small class="text-muted">{{ $analyzer->last_connected_at->diffForHumans() }}</small>
                            @else
                            <span class="text-muted">Never</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th>Created</th><td>{{ $analyzer->created_at->format('d M Y') }}</td></tr>
                </table>
            </div>
        </div>

        <!-- Connection Hint -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ti ti-terminal me-2"></i>Listener Command</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">Start the TCP listener for this analyzer:</p>
                <code class="d-block bg-light p-2 rounded small">
                    php artisan analyzer:listen --analyzer={{ $analyzer->id }}
                </code>
                <p class="small text-muted mt-2 mb-0">Or listen on all active analyzers:</p>
                <code class="d-block bg-light p-2 rounded small mt-1">
                    php artisan analyzer:listen
                </code>
            </div>
        </div>
    </div>

    <!-- Test Mappings -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="ti ti-arrows-exchange me-2"></i>Test Code Mappings ({{ $mappings->count() }})</h6>
                @can('analyzer.manage')
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMappingModal">
                    <i class="ti ti-plus me-1"></i>Add Mapping
                </button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Analyzer Code</th>
                            <th>Lab Test</th>
                            <th>Test Code</th>
                            <th>Conversion Factor</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mappings as $mapping)
                        <tr>
                            <td><code>{{ $mapping->analyzer_test_code }}</code></td>
                            <td>{{ $mapping->labTest->name ?? '—' }}</td>
                            <td><code>{{ $mapping->labTest->code ?? '—' }}</code></td>
                            <td>
                                @if($mapping->unit_conversion_factor != 1.0)
                                <span class="badge bg-warning">× {{ $mapping->unit_conversion_factor }}</span>
                                @else
                                <span class="text-muted">1:1</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('analyzer.manage')
                                <button class="btn btn-sm btn-outline-primary btn-edit-mapping"
                                    data-id="{{ $mapping->id }}"
                                    data-analyzer_test_code="{{ $mapping->analyzer_test_code }}"
                                    data-lab_test_id="{{ $mapping->lab_test_id }}"
                                    data-unit_conversion_factor="{{ $mapping->unit_conversion_factor }}"
                                    title="Edit">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <form action="{{ route('admin.analyzers.mappings.destroy', $mapping) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Remove this mapping?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="ti ti-trash"></i></button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
                                No test mappings configured. Add mappings to enable auto-result matching.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Messages -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="ti ti-message-dots me-2"></i>Recent Messages</h6>
                <a href="{{ route('admin.analyzers.diagnostics', ['analyzer_id' => $analyzer->id]) }}" class="btn btn-sm btn-outline-info">
                    View All
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Protocol</th>
                            <th>Sample ID</th>
                            <th>Status</th>
                            <th>Received</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMessages as $msg)
                        <tr>
                            <td>{{ $msg->id }}</td>
                            <td><span class="badge bg-{{ $msg->protocol === 'hl7' ? 'info' : 'warning' }}">{{ strtoupper($msg->protocol) }}</span></td>
                            <td><code>{{ $msg->sample_id ?: '—' }}</code></td>
                            <td><span class="badge bg-{{ $msg->status_color }}">{{ $msg->status_label }}</span></td>
                            <td><small>{{ $msg->received_at->format('d M H:i:s') }}</small></td>
                            <td><small>{{ number_format(strlen($msg->content)) }} B</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No messages received yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Mapping Modal -->
<div class="modal fade" id="addMappingModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.analyzers.mappings.store', $analyzer) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-plus me-2"></i>Add Test Mapping</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Analyzer Test Code <span class="text-danger">*</span></label>
                        <input type="text" name="analyzer_test_code" class="form-control" required
                               placeholder="Code sent by analyzer (e.g. WBC, HGB, PLT)">
                        <small class="text-muted">The test code as it appears in the analyzer's output message.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Map to Lab Test <span class="text-danger">*</span></label>
                        <select name="lab_test_id" class="form-select" required>
                            <option value="">— Select Lab Test —</option>
                            @foreach($labTests as $test)
                            <option value="{{ $test->id }}">{{ $test->name }} ({{ $test->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Conversion Factor</label>
                        <input type="number" name="unit_conversion_factor" class="form-control" step="0.0001" value="1.0000"
                               placeholder="1.0000">
                        <small class="text-muted">Multiply the analyzer value by this factor. Leave as 1.0 if units match.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Mapping</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Mapping Modal -->
<div class="modal fade" id="editMappingModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editMappingForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-edit me-2"></i>Edit Test Mapping</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Analyzer Test Code <span class="text-danger">*</span></label>
                        <input type="text" name="analyzer_test_code" id="edit_mapping_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Map to Lab Test <span class="text-danger">*</span></label>
                        <select name="lab_test_id" id="edit_mapping_test" class="form-select" required>
                            <option value="">— Select Lab Test —</option>
                            @foreach($labTests as $test)
                            <option value="{{ $test->id }}">{{ $test->name }} ({{ $test->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Conversion Factor</label>
                        <input type="number" name="unit_conversion_factor" id="edit_mapping_factor" class="form-control" step="0.0001">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Mapping</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    // Edit mapping
    $(document).on('click', '.btn-edit-mapping', function() {
        var data = $(this).data();
        var url = "{{ url('admin/analyzers/mappings') }}/" + data.id;
        $('#editMappingForm').attr('action', url);
        $('#edit_mapping_code').val(data.analyzer_test_code);
        $('#edit_mapping_test').val(data.lab_test_id);
        $('#edit_mapping_factor').val(data.unit_conversion_factor);
        new bootstrap.Modal('#editMappingModal').show();
    });
});
</script>
@endsection
