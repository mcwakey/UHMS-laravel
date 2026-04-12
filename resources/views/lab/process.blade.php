@extends('layouts.app')
@section('title', 'Process Lab Request - ' . $request->request_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <a href="{{ route('admin.lab.requests.index') }}" class="text-muted me-2"><i class="ti ti-arrow-left"></i></a>
            Lab Request: {{ $request->request_number }}
        </h4>
    </div>
    <div class="d-flex gap-2">
        @if($request->status === 'pending')
        <form method="POST" action="{{ route('admin.lab.requests.accept', $request) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-success btn-md"><i class="ti ti-check me-1"></i>Accept Request</button>
        </form>
        @endif
        @if(!in_array($request->status, ['completed', 'cancelled']))
        <form method="POST" action="{{ route('admin.lab.requests.cancel', $request) }}" onsubmit="return confirm('Cancel this lab request?')">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-outline-danger btn-md"><i class="ti ti-x me-1"></i>Cancel</button>
        </form>
        @endif
    </div>
</div>

<!-- Request Info -->
<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-info-circle me-1"></i>Request Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Patient</small>
                        <span class="fw-medium">{{ $request->patient->full_name }}</span>
                        <small class="text-muted d-block">{{ $request->patient->patient_number }} &middot; {{ $request->patient->age }}y &middot; {{ $request->patient->gender->value }}</small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-{{ $request->status_color }} px-3 py-2">{{ $request->status_label }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Urgency</small>
                        <span class="badge bg-{{ $request->urgency_color }} px-3 py-2">{{ ucfirst($request->urgency) }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Requested By</small>
                        <span>{{ $request->requestedBy->name ?? '-' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Department</small>
                        <span>{{ $request->department->name ?? '-' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Date</small>
                        <span>{{ $request->created_at->format('d M Y H:i') }}</span>
                    </div>
                    @if($request->clinical_info)
                    <div class="col-12">
                        <small class="text-muted d-block">Clinical Information</small>
                        <p class="mb-0">{{ $request->clinical_info }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-chart-bar me-1"></i>Progress</h6>
            </div>
            <div class="card-body text-center">
                <h1 class="mb-1 {{ $request->completion_percentage === 100 ? 'text-success' : 'text-primary' }}">{{ $request->completion_percentage }}%</h1>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: {{ $request->completion_percentage }}%"></div>
                </div>
                <small class="text-muted">
                    {{ $request->items->where('status', 'completed')->count() }} of {{ $request->items->count() }} tests completed
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Test Items & Results -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="ti ti-flask me-1"></i>Test Items & Results</h6>
        @if($request->status === 'processing')
        @can('lab.results.create')
        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#batchResultForm">
            <i class="ti ti-edit me-1"></i>Batch Enter Results
        </button>
        @endcan
        @endif
    </div>
    <div class="card-body">
        {{-- Batch Entry Form --}}
        @if($request->status === 'processing')
        @can('lab.results.create')
        <div class="collapse mb-3" id="batchResultForm">
            <div class="card card-body bg-light">
                <form method="POST" action="{{ route('admin.lab.results.batch', $request) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Normal Range</th>
                                    <th>Result Value</th>
                                    <th>Abnormal?</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($request->items as $item)
                                @if($item->status !== 'completed')
                                <tr>
                                    <td class="fw-medium">{{ $item->labTest->name }} <small class="text-muted">({{ $item->labTest->code }})</small></td>
                                    <td><small>{{ $item->labTest->normal_range ?? '-' }} {{ $item->labTest->unit ?? '' }}</small></td>
                                    <td>
                                        <input type="text" name="results[{{ $item->id }}][result_value]" class="form-control form-control-sm" placeholder="Enter result">
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input type="hidden" name="results[{{ $item->id }}][is_abnormal]" value="0">
                                            <input type="checkbox" name="results[{{ $item->id }}][is_abnormal]" value="1" class="form-check-input">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="results[{{ $item->id }}][remarks]" class="form-control form-control-sm" placeholder="Optional">
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save Results</button>
                    </div>
                </form>
            </div>
        </div>
        @endcan
        @endif

        {{-- Items List --}}
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Test Name</th>
                        <th>Category</th>
                        <th>Normal Range</th>
                        <th>Status</th>
                        <th>Result</th>
                        <th>Performed By</th>
                        <th>Verified</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($request->items as $index => $item)
                    <tr class="{{ $item->result && $item->result->is_abnormal ? 'table-danger' : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td class="fw-medium">{{ $item->labTest->name }} <small class="text-muted">({{ $item->labTest->code }})</small></td>
                        <td>{{ $item->labTest->category->name ?? '-' }}</td>
                        <td><small>{{ $item->labTest->normal_range ?? '-' }} {{ $item->labTest->unit ?? '' }}</small></td>
                        <td><span class="badge bg-{{ $item->status_color }}">{{ ucfirst($item->status) }}</span></td>
                        <td>
                            @if($item->result)
                                <span class="{{ $item->result->is_abnormal ? 'text-danger fw-bold' : '' }}">
                                    {{ $item->result->result_value }}
                                </span>
                                @if($item->result->is_abnormal)
                                    <i class="ti ti-alert-triangle text-danger ms-1" title="Abnormal"></i>
                                @endif
                                @if($item->result->remarks)
                                    <br><small class="text-muted">{{ $item->result->remarks }}</small>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($item->result)
                                <small>{{ $item->result->performedBy->name ?? '-' }}</small><br>
                                <small class="text-muted">{{ $item->result->performed_at?->format('d M H:i') }}</small>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($item->result && $item->result->is_verified)
                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>Verified</span>
                                <br><small class="text-muted">{{ $item->result->verifiedBy->name ?? '' }}</small>
                            @elseif($item->result)
                                <span class="badge bg-warning">Unverified</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end">
                            @if($item->result && !$item->result->is_verified)
                            @can('lab.results.create')
                            <form method="POST" action="{{ route('admin.lab.results.verify', $item->result) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Verify">
                                    <i class="ti ti-check"></i>
                                </button>
                            </form>
                            @endcan
                            @endif

                            @if($request->status === 'processing' && $item->status !== 'completed')
                            @can('lab.results.create')
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#resultModal-{{ $item->id }}" title="Enter Result">
                                <i class="ti ti-edit"></i>
                            </button>
                            @endcan
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Individual Result Entry Modals --}}
@foreach($request->items as $item)
@if($request->status === 'processing' && $item->status !== 'completed')
<div class="modal fade" id="resultModal-{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.lab.results.store', $item) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Enter Result: {{ $item->labTest->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <small>
                            <strong>Normal Range:</strong> {{ $item->labTest->normal_range ?? 'N/A' }} {{ $item->labTest->unit ?? '' }}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Result Value <span class="text-danger">*</span></label>
                        <textarea name="result_value" class="form-control" rows="2" required placeholder="Enter test result..."></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="hidden" name="is_abnormal" value="0">
                            <input type="checkbox" name="is_abnormal" value="1" class="form-check-input" id="abnormal-{{ $item->id }}">
                            <label class="form-check-label" for="abnormal-{{ $item->id }}">Mark as Abnormal</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Result</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection
