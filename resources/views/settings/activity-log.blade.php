@extends('layouts.app')
@section('title', 'Activity Log')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Settings</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Activity Log</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card">
            <div class="card-body p-0">
                @include('settings.partials.sidebar')
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.settings.activity-log') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Description, type..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Log Name</label>
                        <select name="log_name" class="form-select">
                            <option value="">All</option>
                            @foreach($logNames as $name)
                                <option value="{{ $name }}" {{ request('log_name') === $name ? 'selected' : '' }}>
                                    {{ ucfirst($name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activity Log Table -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Audit Trail</h5>
                <span class="badge bg-secondary">{{ $activities->total() }} entries</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Log</th>
                                <th>Action</th>
                                <th>Subject</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $activity)
                            <tr>
                                <td class="text-nowrap">
                                    <small>{{ $activity->created_at->format('d M Y') }}</small><br>
                                    <small class="text-muted">{{ $activity->created_at->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    @if($activity->causer)
                                        {{ $activity->causer->full_name ?? $activity->causer->name ?? 'System' }}
                                    @else
                                        <span class="text-muted">System</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-dark">{{ $activity->log_name }}</span></td>
                                <td>
                                    @php
                                        $eventColors = [
                                            'created' => 'success',
                                            'updated' => 'info',
                                            'deleted' => 'danger',
                                            'login' => 'primary',
                                            'logout' => 'secondary',
                                        ];
                                        $color = $eventColors[$activity->event ?? ''] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ ucfirst($activity->event ?? $activity->description) }}</span>
                                </td>
                                <td>
                                    @if($activity->subject_type)
                                        <small>{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</small>
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td>
                                    @if($activity->properties && $activity->properties->count())
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#activityModal{{ $activity->id }}">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    @else
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($activity->description, 40) }}</small>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="ti ti-history fs-1 d-block mb-2"></i>
                                    No activity logged yet
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($activities->hasPages())
            <div class="card-footer">
                {{ $activities->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Detail Modals -->
@foreach($activities as $activity)
@if($activity->properties && $activity->properties->count())
<div class="modal fade" id="activityModal{{ $activity->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activity Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Description:</strong> {{ $activity->description }}</p>
                <p><strong>Date:</strong> {{ $activity->created_at->format('d M Y H:i:s') }}</p>
                @if($activity->properties->has('old'))
                    <h6 class="fw-bold mt-3">Changes:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Old Value</th>
                                    <th>New Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activity->properties['attributes'] ?? [] as $key => $value)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                    <td class="text-danger">{{ $activity->properties['old'][$key] ?? '—' }}</td>
                                    <td class="text-success">{{ $value }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <h6 class="fw-bold mt-3">Properties:</h6>
                    <pre class="bg-light p-2 rounded small">{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection
