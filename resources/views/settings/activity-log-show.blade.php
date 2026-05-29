@extends('layouts.app')
@section('title', 'Activity Log Entry')

@section('content')
@php
    $props = $activity->properties instanceof \Illuminate\Support\Collection
        ? $activity->properties->toArray()
        : (array) $activity->properties;
    $module = $props['module'] ?? $activity->log_name ?? 'SYSTEM';
    $severity = $props['severity'] ?? 'INFO';
    $moduleEnum = \App\Enums\LogModule::tryFrom($module);
    $sevEnum = \App\Enums\LogSeverity::tryFrom($severity);
    $action = $props['action'] ?? $activity->event ?? '—';
    $old = $props['old'] ?? [];
    $new = $props['attributes'] ?? [];
    $metadata = $props['metadata'] ?? [];
    $contextKeys = ['patient_id','visit_id','admission_id','emergency_case_id','department_id','invoice_id','claim_id','payment_id','procedure_request_id','theatre_room_id'];
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0">Log Entry #{{ $activity->id }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.logs.index') }}">Activity Logs</a></li>
                <li class="breadcrumb-item active">#{{ $activity->id }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Back</a>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h6 class="card-title mb-0">Overview</h6></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Timestamp</dt>
                    <dd class="col-sm-8">{{ $activity->created_at?->format('d M Y H:i:s') }}</dd>

                    <dt class="col-sm-4">Module</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $moduleEnum?->color() ?? 'secondary' }}-subtle text-{{ $moduleEnum?->color() ?? 'secondary' }}">
                            {{ $moduleEnum?->label() ?? $module }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">Action</dt>
                    <dd class="col-sm-8"><span class="badge bg-light text-dark">{{ $action }}</span></dd>

                    <dt class="col-sm-4">Severity</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $sevEnum?->color() ?? 'secondary' }}-subtle text-{{ $sevEnum?->color() ?? 'secondary' }}">
                            {{ $sevEnum?->label() ?? $severity }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">Causer</dt>
                    <dd class="col-sm-8">
                        @if($activity->causer)
                            {{ $activity->causer->full_name ?? $activity->causer->name ?? $activity->causer->email ?? 'User #' . $activity->causer_id }}
                            <br><small class="text-muted">{{ class_basename($activity->causer_type) }} #{{ $activity->causer_id }}</small>
                        @else
                            <span class="text-muted">System</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Subject</dt>
                    <dd class="col-sm-8">
                        @if($activity->subject_type)
                            {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Description</dt>
                    <dd class="col-sm-8">{{ $activity->description }}</dd>

                    @if(!empty($props['reason']))
                        <dt class="col-sm-4">Reason</dt>
                        <dd class="col-sm-8">{{ $props['reason'] }}</dd>
                    @endif

                    <dt class="col-sm-4">IP</dt>
                    <dd class="col-sm-8"><code class="small">{{ $props['ip'] ?? '—' }}</code></dd>

                    @if(!empty($props['user_agent']))
                        <dt class="col-sm-4">User Agent</dt>
                        <dd class="col-sm-8"><small class="text-muted">{{ $props['user_agent'] }}</small></dd>
                    @endif
                </dl>
            </div>
        </div>

        @php
            $contextRows = array_filter(array_combine($contextKeys, array_map(fn($k) => $props[$k] ?? null, $contextKeys)));
        @endphp
        @if(!empty($contextRows))
            <div class="card mb-3">
                <div class="card-header"><h6 class="card-title mb-0">Context</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        @foreach($contextRows as $k => $v)
                            <dt class="col-sm-5">{{ ucwords(str_replace('_', ' ', $k)) }}</dt>
                            <dd class="col-sm-7">{{ $v }}</dd>
                        @endforeach
                    </dl>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-7">
        @if(!empty($old) || !empty($new))
            <div class="card mb-3">
                <div class="card-header"><h6 class="card-title mb-0">Changes</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Field</th>
                                    <th>Old</th>
                                    <th>New</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $keys = array_unique(array_merge(array_keys((array) $old), array_keys((array) $new))); @endphp
                                @foreach($keys as $key)
                                    <tr>
                                        <td><code class="small">{{ $key }}</code></td>
                                        <td class="text-danger small">{{ is_scalar($old[$key] ?? null) ? ($old[$key] ?? '—') : json_encode($old[$key] ?? null) }}</td>
                                        <td class="text-success small">{{ is_scalar($new[$key] ?? null) ? ($new[$key] ?? '—') : json_encode($new[$key] ?? null) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($metadata))
            <div class="card mb-3">
                <div class="card-header"><h6 class="card-title mb-0">Metadata</h6></div>
                <div class="card-body">
                    <pre class="bg-light p-2 rounded small mb-0">{{ json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h6 class="card-title mb-0">Raw Properties</h6></div>
            <div class="card-body">
                <pre class="bg-light p-2 rounded small mb-0">{{ json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection
