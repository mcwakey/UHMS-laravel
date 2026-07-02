@extends('layouts.app')
@section('title', __('patients.privacy.patient_privacy_audit'))

@section('content')
<x-page-header-back
    :title="__('patients.privacy.patient_privacy_audit')"
    :href="route('admin.patients.index')"
/>

<div class="card mb-3">
    <div class="card-header">
        <h6 class="fw-bold mb-0"><i class="ti ti-filter me-1"></i>{{ __('patients.privacy.audit_filters') }}</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">{{ __('common.patient') }}</label>
                <input type="number" name="patient_id" class="form-control" value="{{ request('patient_id') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('common.user') }}</label>
                <input type="number" name="user_id" class="form-control" value="{{ request('user_id') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('common.actions') }}</label>
                <input type="text" name="action" class="form-control" value="{{ request('action') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('common.from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('common.to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-primary"><i class="ti ti-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('common.date') }}</th>
                        <th>{{ __('common.actions') }}</th>
                        <th>{{ __('common.user') }}</th>
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('common.summary') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $event)
                        @php
                            $properties = $event->properties?->toArray() ?? [];
                            $metadata = data_get($properties, 'metadata', []);
                        @endphp
                        <tr>
                            <td>{{ $event->created_at?->format('d M Y H:i') }}</td>
                            <td><span class="badge bg-dark">{{ $event->event }}</span></td>
                            <td>{{ $event->causer?->name ?? '—' }}</td>
                            <td>{{ data_get($properties, 'patient_id', $event->patient_id) ?? '—' }}</td>
                            <td>
                                @if(! empty($metadata))
                                    <code class="small">{{ json_encode($metadata) }}</code>
                                @else
                                    {{ $event->description }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state icon="ti-shield-lock" :title="__('patients.privacy.no_privacy_audit_events')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($logs, 'links'))
        <div class="card-footer">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
