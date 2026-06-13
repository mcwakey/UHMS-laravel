@extends('layouts.app')
@section('title', __('analyzers.analyzer_detail_title', ['name' => $analyzer->name]))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <a href="{{ route('admin.analyzers.index') }}" class="text-muted text-decoration-none">
                <i class="ti ti-device-analytics me-1"></i>{{ __('analyzers.analyzers') }}
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
            {{ $analyzer->is_active ? __('common.active') : __('common.inactive') }}
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
                <h6 class="mb-0"><i class="ti ti-info-circle me-2"></i>{{ __('analyzers.device_information') }}</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <tr><th class="w-50">{{ __('common.name') }}</th><td>{{ $analyzer->name }}</td></tr>
                    <tr><th>{{ __('analyzers.manufacturer') }}</th><td>{{ $analyzer->manufacturer ?: '—' }}</td></tr>
                    <tr><th>{{ __('analyzers.model') }}</th><td>{{ $analyzer->model ?: '—' }}</td></tr>
                    <tr><th>{{ __('analyzers.protocol') }}</th><td>{{ $analyzer->protocol_label }}</td></tr>
                    <tr>
                        <th>{{ __('analyzers.connection') }}</th>
                        <td>
                            <i class="ti ti-{{ $analyzer->connection_type === 'tcp' ? 'network' : 'usb' }} me-1"></i>
                            {{ $analyzer->connection_info }}
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('analyzers.last_connected') }}</th>
                        <td>
                            @if($analyzer->last_connected_at)
                            {{ $analyzer->last_connected_at->format('d M Y H:i') }}
                            <br><small class="text-muted">{{ $analyzer->last_connected_at->diffForHumans() }}</small>
                            @else
                            <span class="text-muted">{{ __('analyzers.never') }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th>{{ __('analyzers.created') }}</th><td>{{ $analyzer->created_at->format('d M Y') }}</td></tr>
                </table></div>
            </div>
        </div>

        <!-- Connection Hint -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ti ti-terminal me-2"></i>{{ __('analyzers.listener_command') }}</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">{{ __('analyzers.start_tcp_listener') }}</p>
                <code class="d-block bg-light p-2 rounded small">
                    php artisan analyzer:listen --analyzer={{ $analyzer->id }}
                </code>
                <p class="small text-muted mt-2 mb-0">{{ __('analyzers.or_listen_all') }}</p>
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
                <h6 class="mb-0"><i class="ti ti-arrows-exchange me-2"></i>{{ __('analyzers.test_code_mappings') }} ({{ $mappings->count() }})</h6>
                @can('analyzer.manage')
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMappingModal">
                    <i class="ti ti-plus me-1"></i>{{ __('analyzers.add_mapping') }}
                </button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('analyzers.analyzer_code') }}</th>
                            <th>{{ __('analyzers.lab_test') }}</th>
                            <th>{{ __('analyzers.test_code') }}</th>
                            <th>{{ __('analyzers.conversion_factor') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
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
                                    title="{{ __('common.edit') }}">
                                    <i class="ti ti-edit"></i>
                                </button>
                                <form action="{{ route('admin.analyzers.mappings.destroy', $mapping) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('{{ __('analyzers.remove_mapping_confirm') }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="{{ __('common.delete') }}"><i class="ti ti-trash"></i></button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5"><x-empty-state :message="__('analyzers.no_mappings')" /></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Messages -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="ti ti-message-dots me-2"></i>{{ __('analyzers.recent_messages') }}</h6>
                <a href="{{ route('admin.analyzers.diagnostics', ['analyzer_id' => $analyzer->id]) }}" class="btn btn-sm btn-outline-info">
                    {{ __('common.view_all') }}
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('analyzers.protocol') }}</th>
                            <th>{{ __('analyzers.sample_id') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('analyzers.received') }}</th>
                            <th>{{ __('analyzers.size') }}</th>
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
                            <td colspan="6"><x-empty-state :message="__('analyzers.no_messages_received')" /></td>
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
                    <h5 class="modal-title"><i class="ti ti-plus me-2"></i>{{ __('analyzers.add_test_mapping') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('analyzers.analyzer_test_code') }} <span class="text-danger">*</span></label>
                        <input type="text" name="analyzer_test_code" class="form-control" required
                               placeholder="{{ __('analyzers.analyzer_test_code_placeholder') }}">
                        <small class="text-muted">{{ __('analyzers.analyzer_test_code_hint') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('analyzers.map_to_lab_test') }} <span class="text-danger">*</span></label>
                        <select name="lab_test_id" class="form-select" required>
                            <option value="">{{ __('analyzers.select_lab_test') }}</option>
                            @foreach($labTests as $test)
                            <option value="{{ $test->id }}">{{ $test->name }} ({{ $test->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('analyzers.unit_conversion_factor') }}</label>
                        <input type="number" name="unit_conversion_factor" class="form-control" step="0.0001" value="1.0000"
                               placeholder="1.0000">
                        <small class="text-muted">{{ __('analyzers.unit_conversion_hint') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('analyzers.add_mapping') }}</button>
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
                    <h5 class="modal-title"><i class="ti ti-edit me-2"></i>{{ __('analyzers.edit_test_mapping') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('analyzers.analyzer_test_code') }} <span class="text-danger">*</span></label>
                        <input type="text" name="analyzer_test_code" id="edit_mapping_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('analyzers.map_to_lab_test') }} <span class="text-danger">*</span></label>
                        <select name="lab_test_id" id="edit_mapping_test" class="form-select" required>
                            <option value="">{{ __('analyzers.select_lab_test') }}</option>
                            @foreach($labTests as $test)
                            <option value="{{ $test->id }}">{{ $test->name }} ({{ $test->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('analyzers.unit_conversion_factor') }}</label>
                        <input type="number" name="unit_conversion_factor" id="edit_mapping_factor" class="form-control" step="0.0001">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('analyzers.update_mapping') }}</button>
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
