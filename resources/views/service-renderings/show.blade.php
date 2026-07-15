@extends('layouts.app')
@section('title', __('services.rendering_title'))

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1"><i class="ti ti-clipboard-check me-2 text-primary"></i>{{ $rendering->service?->name ?? __('services.rendering_title') }}</h4>
        <p class="text-muted mb-0">{{ $rendering->patient?->full_name ?? __('services.unknown_patient') }} / {{ $rendering->visit?->visit_number ?? __('services.no_visit') }}</p>
    </div>
    <div class="d-flex gap-2">
        @if($rendering->visit)
            <a href="{{ $workspaceRoutes->isNursing() ? $workspaceRoutes->opdVisitShow($rendering->visit) : route('admin.visits.preview', $rendering->visit) }}" class="btn btn-outline-info btn-sm">
                <i class="ti ti-eye-search me-1"></i>{{ __('services.visit_preview') }}
            </a>
        @endif
        <a href="{{ $workspaceRoutes->route('admin.service-renderings.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $paymentStatus = $rendering->invoiceItem?->payment_status ?? 'unpaid';
    $isUnrenderedButPayable = $rendering->status === \App\Models\ServiceRendering::STATUS_NOT_RENDERED
        && ! in_array($paymentStatus, ['cancelled', 'voided', 'waived'], true)
        && (float) ($rendering->invoiceItem?->patient_payable ?? 0) > 0;
@endphp

@if($isUnrenderedButPayable)
    <div class="alert alert-warning">
        {{ __('services.financially_active_warning') }}
    </div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <span class="text-muted small">{{ __('services.rendering_status') }}</span>
                        <div><span class="badge bg-{{ $rendering->status_color }} fs-6">{{ __("statuses.default.$rendering->status") }}</span></div>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small">{{ __('services.payment_status') }}</span>
                        <div><span class="badge bg-{{ $paymentStatus === 'paid' ? 'success' : ($paymentStatus === 'partially_paid' ? 'warning text-dark' : 'danger') }}">{{ __("statuses.default.$paymentStatus") }}</span></div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('common.patient') }}</div>
                        <div class="fw-semibold">{{ $rendering->patient?->full_name ?? __('services.unknown_patient') }}</div>
                        <small class="text-muted">{{ $rendering->patient?->patient_number }}</small>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('services.visit') }}</div>
                        <div class="fw-semibold">{{ $rendering->visit?->visit_number ?? __('services.no_visit_number') }}</div>
                        <small class="text-muted">{{ $rendering->visit?->visit_type?->translatedLabel() ?? '' }}</small>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('services.service_name') }}</div>
                        <div class="fw-semibold">{{ $rendering->service?->name ?? __('services.service_name') }}</div>
                        <small class="text-muted">{{ $rendering->invoiceItem?->description }}</small>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('common.department') }}</div>
                        <div class="fw-semibold">{{ $rendering->department?->name ?? __('services.unassigned') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('services.invoice') }}</div>
                        <div class="fw-semibold">{{ $rendering->invoiceItem?->invoice?->invoice_number ?? __('services.no_invoice') }}</div>
                        <small class="text-muted">{{ __('services.invoice_item', ['id' => $rendering->invoice_item_id]) }}</small>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('services.amount') }}</div>
                        <div class="fw-semibold">GHS {{ number_format((float) ($rendering->invoiceItem?->patient_payable ?? 0), 2) }}</div>
                        <small class="text-muted">{{ __('services.invoice_state_note') }}</small>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('services.started_by') }}</div>
                        <div>{{ $rendering->startedBy?->full_name ?? $rendering->startedBy?->name ?? __('services.not_started') }}</div>
                        <small class="text-muted">{{ $rendering->started_at?->format('d M Y H:i') }}</small>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('services.rendered_by') }}</div>
                        <div>{{ $rendering->renderedBy?->full_name ?? $rendering->renderedBy?->name ?? __('services.not_rendered_by_anyone') }}</div>
                        <small class="text-muted">{{ $rendering->rendered_at?->format('d M Y H:i') }}</small>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">{{ __('services.context') }}</div>
                        <div>
                            @if($rendering->emergencyCase)
                                <span class="badge bg-danger-subtle text-danger">{{ $rendering->emergencyCase->emergency_number }}</span>
                            @elseif($rendering->admission)
                                <span class="badge bg-primary-subtle text-primary">{{ $rendering->admission->admission_number }}</span>
                            @elseif($rendering->consultationRoute)
                                <span class="badge bg-info-subtle text-info">{{ __('services.consultation_route', ['id' => $rendering->consultationRoute->id]) }}</span>
                            @else
                                <span class="badge bg-light text-dark">{{ __('services.visit_service') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($rendering->result_summary || $rendering->notes || $rendering->reason_not_rendered)
                    <hr>
                    @if($rendering->result_summary)
                        <h6 class="fw-semibold">{{ __('services.result_summary') }}</h6>
                        <p class="mb-3">{{ $rendering->result_summary }}</p>
                    @endif
                    @if($rendering->reason_not_rendered)
                        <h6 class="fw-semibold">{{ __('services.reason_not_rendered') }}</h6>
                        <p class="mb-3">{{ $rendering->reason_not_rendered }}</p>
                    @endif
                    @if($rendering->notes)
                        <h6 class="fw-semibold">{{ __('common.notes') }}</h6>
                        <p class="mb-0">{{ $rendering->notes }}</p>
                    @endif
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">{{ __('services.audit_trail') }}</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('services.time') }}</th>
                                <th>{{ __('services.action') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('services.user') }}</th>
                                <th>{{ __('common.notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rendering->logs->sortByDesc('created_at') as $log)
                                <tr>
                                    <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                                    <td>{{ __("statuses.default.$log->action") }}</td>
                                    <td>{{ $log->from_status ? __("statuses.default.$log->from_status").' -> ' : '' }}{{ $log->to_status ? __("statuses.default.$log->to_status") : '' }}</td>
                                    <td>{{ $log->performedBy?->full_name ?? $log->performedBy?->name ?? __('services.system') }}</td>
                                    <td>{{ $log->reason ?: $log->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-empty-state :message="__('services.no_rendering_logs')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        @can('service_rendering.start')
            @if($rendering->can_be_started)
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-semibold">{{ __('services.start_rendering') }}</h6>
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.service-renderings.start', $rendering) }}">
                            @csrf
                            <textarea class="form-control mb-2" name="notes" rows="2" placeholder="{{ __('services.optional_start_note') }}"></textarea>
                            <button class="btn btn-info w-100" type="submit">{{ __('services.start_service') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        @can('service_rendering.mark_rendered')
            @if($rendering->can_be_closed)
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-semibold">{{ __('services.mark_rendered') }}</h6>
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.service-renderings.mark-rendered', $rendering) }}">
                            @csrf
                            <label class="form-label small">{{ __('services.rendered_at') }}</label>
                            <input type="datetime-local" class="form-control mb-2" name="rendered_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                            <label class="form-label small">{{ __('services.result_summary') }}</label>
                            <textarea class="form-control mb-2" name="result_summary" rows="3" placeholder="{{ __('services.result_placeholder') }}"></textarea>
                            <label class="form-label small">{{ __('common.notes') }}</label>
                            <textarea class="form-control mb-2" name="notes" rows="2"></textarea>
                            <button class="btn btn-success w-100" type="submit">{{ __('services.mark_rendered') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        @can('service_rendering.mark_not_rendered')
            @if($rendering->can_be_closed)
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-semibold">{{ __('services.mark_not_rendered') }}</h6>
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.service-renderings.mark-not-rendered', $rendering) }}">
                            @csrf
                            <label class="form-label small">{{ __('services.reason') }}</label>
                            <textarea class="form-control mb-2" name="reason_not_rendered" rows="3" required></textarea>
                            <label class="form-label small">{{ __('common.notes') }}</label>
                            <textarea class="form-control mb-2" name="notes" rows="2"></textarea>
                            <button class="btn btn-outline-danger w-100" type="submit">{{ __('services.mark_not_rendered') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        @can('service_rendering.edit_notes')
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-semibold">{{ __('services.update_notes') }}</h6>
                    <form method="POST" action="{{ $workspaceRoutes->route('admin.service-renderings.notes', $rendering) }}">
                        @csrf
                        @method('PATCH')
                        <label class="form-label small">{{ __('services.result_summary') }}</label>
                        <textarea class="form-control mb-2" name="result_summary" rows="3">{{ old('result_summary', $rendering->result_summary) }}</textarea>
                        <label class="form-label small">{{ __('common.notes') }}</label>
                        <textarea class="form-control mb-2" name="notes" rows="3">{{ old('notes', $rendering->notes) }}</textarea>
                        <button class="btn btn-outline-primary w-100" type="submit">{{ __('services.save_notes') }}</button>
                    </form>
                </div>
            </div>
        @endcan

        @can('service_rendering.cancel')
            @if($rendering->status !== \App\Models\ServiceRendering::STATUS_CANCELLED)
                <div class="card border-danger">
                    <div class="card-body">
                        <h6 class="fw-semibold text-danger">{{ __('services.cancel_rendering') }}</h6>
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.service-renderings.cancel', $rendering) }}">
                            @csrf
                            <label class="form-label small">{{ __('services.reason') }}</label>
                            <textarea class="form-control mb-2" name="reason" rows="3" required></textarea>
                            <label class="form-label small">{{ __('common.notes') }}</label>
                            <textarea class="form-control mb-2" name="notes" rows="2"></textarea>
                            <button class="btn btn-outline-danger w-100" type="submit">{{ __('services.cancel_rendering') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan
    </div>
</div>
@endsection
