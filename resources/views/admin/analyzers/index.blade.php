@extends('layouts.app')
@section('title', 'Lab Analyzers')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-device-analytics me-2"></i>Lab Analyzers</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.analyzers.diagnostics') }}" class="btn btn-outline-info btn-md">
            <i class="ti ti-activity me-1"></i>Message Log
        </a>
        @can('analyzer.manage')
        <button class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#addAnalyzerModal">
            <i class="ti ti-plus me-1"></i>Add Analyzer
        </button>
        @endcan
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

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-5 fw-bold">{{ $stats['total'] }}</div>
                        <small>Total Devices</small>
                    </div>
                    <i class="ti ti-device-analytics fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-5 fw-bold">{{ $stats['active'] }}</div>
                        <small>Active</small>
                    </div>
                    <i class="ti ti-plug-connected fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-5 fw-bold">{{ $stats['hl7'] }}</div>
                        <small>HL7 Devices</small>
                    </div>
                    <i class="ti ti-binary-tree fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-5 fw-bold">{{ $stats['total_mappings'] }}</div>
                        <small>Test Mappings</small>
                    </div>
                    <i class="ti ti-arrows-exchange fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.analyzers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search device..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="protocol" class="form-select form-select-sm">
                    <option value="">All Protocols</option>
                    <option value="hl7" {{ request('protocol') === 'hl7' ? 'selected' : '' }}>HL7 v2.x</option>
                    <option value="astm" {{ request('protocol') === 'astm' ? 'selected' : '' }}>ASTM E1394</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
            @if(request()->hasAny(['search','protocol','status']))
            <div class="col-md-2">
                <a href="{{ route('admin.analyzers.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

<!-- Analyzer List -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Device</th>
                    <th>Protocol</th>
                    <th>Connection</th>
                    <th>Mappings</th>
                    <th>Messages</th>
                    <th>Last Connected</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($analyzers as $analyzer)
                <tr>
                    <td>
                        <a href="{{ route('admin.analyzers.show', $analyzer) }}" class="fw-semibold text-decoration-none">
                            {{ $analyzer->name }}
                        </a>
                        @if($analyzer->manufacturer || $analyzer->model)
                        <br><small class="text-muted">{{ $analyzer->manufacturer }} {{ $analyzer->model }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $analyzer->protocol === 'hl7' ? 'info' : 'warning' }}">
                            {{ $analyzer->protocol_label }}
                        </span>
                    </td>
                    <td>
                        <small>
                            <i class="ti ti-{{ $analyzer->connection_type === 'tcp' ? 'network' : 'usb' }} me-1"></i>
                            {{ $analyzer->connection_info }}
                        </small>
                    </td>
                    <td>{{ $analyzer->test_mappings_count ?? 0 }}</td>
                    <td>{{ $analyzer->raw_messages_count ?? 0 }}</td>
                    <td>
                        @if($analyzer->last_connected_at)
                        <small title="{{ $analyzer->last_connected_at->format('d M Y H:i') }}">
                            {{ $analyzer->last_connected_at->diffForHumans() }}
                        </small>
                        @else
                        <small class="text-muted">Never</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $analyzer->status_color }}">
                            {{ $analyzer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.analyzers.show', $analyzer) }}" class="btn btn-outline-info" title="View">
                                <i class="ti ti-eye"></i>
                            </a>
                            @can('analyzer.manage')
                            <button class="btn btn-outline-primary btn-edit-analyzer"
                                data-id="{{ $analyzer->id }}"
                                data-name="{{ $analyzer->name }}"
                                data-model="{{ $analyzer->model }}"
                                data-manufacturer="{{ $analyzer->manufacturer }}"
                                data-protocol="{{ $analyzer->protocol }}"
                                data-connection_type="{{ $analyzer->connection_type }}"
                                data-ip_address="{{ $analyzer->ip_address }}"
                                data-port="{{ $analyzer->port }}"
                                data-com_port="{{ $analyzer->com_port }}"
                                data-baud_rate="{{ $analyzer->baud_rate }}"
                                title="Edit">
                                <i class="ti ti-edit"></i>
                            </button>
                            <form action="{{ route('admin.analyzers.toggle', $analyzer) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-outline-{{ $analyzer->is_active ? 'warning' : 'success' }}" title="{{ $analyzer->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="ti ti-{{ $analyzer->is_active ? 'player-pause' : 'player-play' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.analyzers.destroy', $analyzer) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this analyzer? This will also remove all its test mappings.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger" title="Delete"><i class="ti ti-trash"></i></button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="ti ti-device-analytics fs-1 d-block mb-2 opacity-50"></i>
                        No analyzer devices configured yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($analyzers->hasPages())
    <div class="card-footer">{{ $analyzers->links() }}</div>
    @endif
</div>

<!-- Add Analyzer Modal -->
<div class="modal fade" id="addAnalyzerModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.analyzers.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-plus me-2"></i>Add Analyzer Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Device Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Mindray BC-5000">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Manufacturer</label>
                            <input type="text" name="manufacturer" class="form-control" placeholder="e.g. Mindray">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" class="form-control" placeholder="e.g. BC-5000">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Protocol <span class="text-danger">*</span></label>
                            <select name="protocol" class="form-select" required>
                                <option value="hl7">HL7 v2.x</option>
                                <option value="astm">ASTM E1394</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Connection Type <span class="text-danger">*</span></label>
                            <select name="connection_type" class="form-select conn-type-select" required>
                                <option value="tcp">TCP/IP</option>
                                <option value="serial">Serial (COM)</option>
                            </select>
                        </div>
                    </div>
                    <div class="tcp-fields">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">IP Address</label>
                                <input type="text" name="ip_address" class="form-control" placeholder="192.168.1.100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Port</label>
                                <input type="number" name="port" class="form-control" placeholder="9100" min="1" max="65535">
                            </div>
                        </div>
                    </div>
                    <div class="serial-fields" style="display:none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">COM Port</label>
                                <input type="text" name="com_port" class="form-control" placeholder="COM3">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Baud Rate</label>
                                <select name="baud_rate" class="form-select">
                                    <option value="9600" selected>9600</option>
                                    <option value="19200">19200</option>
                                    <option value="38400">38400</option>
                                    <option value="57600">57600</option>
                                    <option value="115200">115200</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Analyzer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Analyzer Modal -->
<div class="modal fade" id="editAnalyzerModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editAnalyzerForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-edit me-2"></i>Edit Analyzer Device</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Device Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Manufacturer</label>
                            <input type="text" name="manufacturer" id="edit_manufacturer" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" id="edit_model" class="form-control">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Protocol <span class="text-danger">*</span></label>
                            <select name="protocol" id="edit_protocol" class="form-select" required>
                                <option value="hl7">HL7 v2.x</option>
                                <option value="astm">ASTM E1394</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Connection Type <span class="text-danger">*</span></label>
                            <select name="connection_type" id="edit_connection_type" class="form-select conn-type-select" required>
                                <option value="tcp">TCP/IP</option>
                                <option value="serial">Serial (COM)</option>
                            </select>
                        </div>
                    </div>
                    <div class="tcp-fields">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">IP Address</label>
                                <input type="text" name="ip_address" id="edit_ip_address" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Port</label>
                                <input type="number" name="port" id="edit_port" class="form-control" min="1" max="65535">
                            </div>
                        </div>
                    </div>
                    <div class="serial-fields" style="display:none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">COM Port</label>
                                <input type="text" name="com_port" id="edit_com_port" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Baud Rate</label>
                                <select name="baud_rate" id="edit_baud_rate" class="form-select">
                                    <option value="9600">9600</option>
                                    <option value="19200">19200</option>
                                    <option value="38400">38400</option>
                                    <option value="57600">57600</option>
                                    <option value="115200">115200</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Analyzer</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    // Connection type toggle
    $(document).on('change', '.conn-type-select', function() {
        var modal = $(this).closest('.modal-content');
        if ($(this).val() === 'serial') {
            modal.find('.tcp-fields').hide();
            modal.find('.serial-fields').show();
        } else {
            modal.find('.tcp-fields').show();
            modal.find('.serial-fields').hide();
        }
    });

    // Edit button
    $(document).on('click', '.btn-edit-analyzer', function() {
        var data = $(this).data();
        var url = "{{ route('admin.analyzers.index') }}/" + data.id;
        $('#editAnalyzerForm').attr('action', url);
        $('#edit_name').val(data.name);
        $('#edit_manufacturer').val(data.manufacturer);
        $('#edit_model').val(data.model);
        $('#edit_protocol').val(data.protocol);
        $('#edit_connection_type').val(data.connection_type).trigger('change');
        $('#edit_ip_address').val(data.ip_address);
        $('#edit_port').val(data.port);
        $('#edit_com_port').val(data.com_port);
        $('#edit_baud_rate').val(data.baud_rate);
        new bootstrap.Modal('#editAnalyzerModal').show();
    });
});
</script>
@endsection
