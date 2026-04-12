@extends('layouts.app')
@section('title', 'Analyzer Diagnostics')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <a href="{{ route('admin.analyzers.index') }}" class="text-muted text-decoration-none">
                <i class="ti ti-device-analytics me-1"></i>Analyzers
            </a>
            <i class="ti ti-chevron-right mx-1 fs-6 text-muted"></i>
            Message Log & Diagnostics
        </h4>
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
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body py-2 text-center">
                <div class="fs-5 fw-bold">{{ $msgStats['received'] }}</div>
                <small>Received</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body py-2 text-center">
                <div class="fs-5 fw-bold">{{ $msgStats['processing'] }}</div>
                <small>Processing</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body py-2 text-center">
                <div class="fs-5 fw-bold">{{ $msgStats['processed'] }}</div>
                <small>Processed</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body py-2 text-center">
                <div class="fs-5 fw-bold">{{ $msgStats['failed'] }}</div>
                <small>Failed</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body py-2 text-center">
                <div class="fs-5 fw-bold">{{ $msgStats['today_total'] }}</div>
                <small>Today Total</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body py-2 text-center">
                <div class="fs-5 fw-bold">{{ $msgStats['today_processed'] }}</div>
                <small>Today Processed</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.analyzers.diagnostics') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <select name="analyzer_id" class="form-select form-select-sm">
                    <option value="">All Analyzers</option>
                    @foreach($analyzers as $a)
                    <option value="{{ $a->id }}" {{ request('analyzer_id') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Received</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>Processed</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="duplicate" {{ request('status') === 'duplicate' ? 'selected' : '' }}>Duplicate</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="protocol" class="form-select form-select-sm">
                    <option value="">All Protocols</option>
                    <option value="hl7" {{ request('protocol') === 'hl7' ? 'selected' : '' }}>HL7</option>
                    <option value="astm" {{ request('protocol') === 'astm' ? 'selected' : '' }}>ASTM</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="sample_id" class="form-control form-control-sm" placeholder="Sample ID..." value="{{ request('sample_id') }}">
            </div>
            <div class="col-md-1">
                <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
            @if(request()->hasAny(['analyzer_id','status','protocol','sample_id']))
            <div class="col-md-1">
                <a href="{{ route('admin.analyzers.diagnostics') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

<!-- Message Log -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Analyzer</th>
                    <th>Protocol</th>
                    <th>Direction</th>
                    <th>Sample ID</th>
                    <th>Status</th>
                    <th>Attempts</th>
                    <th>Received</th>
                    <th>Processed</th>
                    <th>Size</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $msg)
                <tr>
                    <td>{{ $msg->id }}</td>
                    <td>
                        @if($msg->analyzer)
                        <a href="{{ route('admin.analyzers.show', $msg->analyzer) }}">{{ $msg->analyzer->name }}</a>
                        @else
                        <span class="text-muted">Unknown</span>
                        @endif
                    </td>
                    <td><span class="badge bg-{{ $msg->protocol === 'hl7' ? 'info' : 'warning' }}">{{ strtoupper($msg->protocol) }}</span></td>
                    <td>
                        <i class="ti ti-{{ $msg->direction === 'inbound' ? 'arrow-down-left' : 'arrow-up-right' }} me-1"></i>
                        {{ ucfirst($msg->direction) }}
                    </td>
                    <td><code>{{ $msg->sample_id ?: '—' }}</code></td>
                    <td><span class="badge bg-{{ $msg->status_color }}">{{ $msg->status_label }}</span></td>
                    <td>{{ $msg->processing_attempts }}</td>
                    <td><small>{{ $msg->received_at->format('d M H:i:s') }}</small></td>
                    <td>
                        @if($msg->processed_at)
                        <small>{{ $msg->processed_at->format('d M H:i:s') }}</small>
                        @else
                        <small class="text-muted">—</small>
                        @endif
                    </td>
                    <td><small>{{ number_format(strlen($msg->content)) }} B</small></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-info btn-view-message" data-id="{{ $msg->id }}" data-content="{{ e(substr($msg->content, 0, 2000)) }}" data-error="{{ e($msg->error_message) }}" title="View">
                                <i class="ti ti-eye"></i>
                            </button>
                            @if(in_array($msg->processing_status, ['failed', 'received']))
                            @can('analyzer.manage')
                            <form action="{{ route('admin.analyzers.reprocess', $msg) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-outline-warning" title="Reprocess"><i class="ti ti-refresh"></i></button>
                            </form>
                            @endcan
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center text-muted py-4">
                        <i class="ti ti-message-dots fs-1 d-block mb-2 opacity-50"></i>
                        No messages found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($messages->hasPages())
    <div class="card-footer">{{ $messages->links() }}</div>
    @endif
</div>

<!-- View Message Modal -->
<div class="modal fade" id="viewMessageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-message-dots me-2"></i>Raw Message <span id="view_msg_id"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="view_error_section" class="alert alert-danger mb-3" style="display:none;">
                    <strong>Error:</strong> <span id="view_msg_error"></span>
                </div>
                <label class="form-label fw-semibold">Message Content</label>
                <pre class="bg-light p-3 rounded border" style="max-height:400px; overflow-y:auto; white-space:pre-wrap; word-break:break-all; font-size:0.8rem;" id="view_msg_content"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    $(document).on('click', '.btn-view-message', function() {
        var data = $(this).data();
        $('#view_msg_id').text('#' + data.id);
        $('#view_msg_content').text(data.content);

        if (data.error) {
            $('#view_error_section').show();
            $('#view_msg_error').text(data.error);
        } else {
            $('#view_error_section').hide();
        }

        new bootstrap.Modal('#viewMessageModal').show();
    });
});
</script>
@endsection
