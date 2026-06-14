@extends('layouts.app')
@section('title', __('settings.activity_logs_breadcrumb'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('settings.activity_title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">{{ __('settings.logs_breadcrumb') }}</li>
            </ol>
        </nav>
    </div>
    @can('logs.export')
        <a href="{{ route('admin.logs.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-download me-1"></i>{{ __('settings.export_csv') }}
        </a>
    @endcan
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1">{{ __('settings.search_label') }}</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Description, subject..."
                       value="{{ $filters['search'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('settings.module_label') }}</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">{{ __('settings.all_option') }}</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod->value }}" {{ $filters['module'] === $mod->value ? 'selected' : '' }}>
                            {{ $mod->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('settings.severity_label') }}</label>
                <select name="severity" class="form-select form-select-sm">
                    <option value="">{{ __('settings.all_option') }}</option>
                    @foreach($severities as $sev)
                        <option value="{{ $sev->value }}" {{ $filters['severity'] === $sev->value ? 'selected' : '' }}>
                            {{ $sev->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('settings.action_label') }}</label>
                <input type="text" name="action" class="form-control form-control-sm" placeholder="CREATED, LOGIN..."
                       value="{{ $filters['action'] }}">
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1">{{ __('settings.user_id_label') }}</label>
                <input type="number" name="user_id" class="form-control form-control-sm" value="{{ $filters['user_id'] }}">
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1">{{ __('settings.patient_label') }}</label>
                <input type="number" name="patient_id" class="form-control form-control-sm" value="{{ $filters['patient_id'] }}">
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1">{{ __('settings.visit_label') }}</label>
                <input type="number" name="visit_id" class="form-control form-control-sm" value="{{ $filters['visit_id'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('settings.date_from_label') }}</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">{{ __('settings.date_to_label') }}</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="ti ti-filter me-1"></i>{{ __('settings.filter_btn') }}</button>
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm" aria-label="Close" title="Close"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">{{ __('settings.audit_trail') }}</h5>
        <span class="badge bg-secondary">{{ __('settings.entries_count', ['count' => $totalCount]) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('settings.activity_date') }}</th>
                        <th>{{ __('settings.module_label') }}</th>
                        <th>{{ __('settings.action_label') }}</th>
                        <th>{{ __('settings.severity_label') }}</th>
                        <th>{{ __('settings.user_label') }}</th>
                        <th>{{ __('settings.subject_label') }}</th>
                        <th>{{ __('settings.activity_description') }}</th>
                        <th class="text-end">{{ __('settings.details_label') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                        @php
                            $props = $activity->properties instanceof \Illuminate\Support\Collection
                                ? $activity->properties->toArray()
                                : (array) $activity->properties;
                            $module = $props['module'] ?? $activity->log_name ?? 'SYSTEM';
                            $severity = $props['severity'] ?? 'INFO';
                            $moduleEnum = \App\Enums\LogModule::tryFrom($module);
                            $sevEnum = \App\Enums\LogSeverity::tryFrom($severity);
                            $action = $props['action'] ?? $activity->event ?? '—';
                        @endphp
                        <tr>
                            <td class="text-nowrap">
                                <small>{{ $activity->created_at?->format('d M Y H:i:s') }}</small>
                            </td>
                            <td>
                                <span class="badge bg-{{ $moduleEnum?->color() ?? 'secondary' }}-subtle text-{{ $moduleEnum?->color() ?? 'secondary' }}">
                                    {{ $moduleEnum?->label() ?? $module }}
                                </span>
                            </td>
                            <td><span class="badge bg-light text-dark">{{ $action }}</span></td>
                            <td>
                                <span class="badge bg-{{ $sevEnum?->color() ?? 'secondary' }}-subtle text-{{ $sevEnum?->color() ?? 'secondary' }}">
                                    {{ $sevEnum?->label() ?? $severity }}
                                </span>
                            </td>
                            <td>
                                @if($activity->causer)
                                    <small>{{ $activity->causer->full_name ?? $activity->causer->name ?? $activity->causer->email ?? 'User #' . $activity->causer_id }}</small>
                                @else
                                    <small class="text-muted">{{ __('settings.system_label') }}</small>
                                @endif
                            </td>
                            <td>
                                @if($activity->subject_type)
                                    <small class="text-muted">{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</small>
                                @else
                                    <small class="text-muted">—</small>
                                @endif
                            </td>
                            <td>
                                <small>{{ \Illuminate\Support\Str::limit((string) $activity->description, 60) }}</small>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.logs.show', $activity->id) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('settings.view_details') }}">
                                    <i class="ti ti-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="ti ti-history fs-1 d-block mb-2"></i>
                                {{ __('settings.no_activity_matches') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($activities->hasPages())
        <div class="card-footer">{{ $activities->links() }}</div>
    @endif
</div>
@endsection
