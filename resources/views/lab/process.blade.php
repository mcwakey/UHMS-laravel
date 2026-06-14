@extends('layouts.app')
@section('title', 'Investigation: ' . $request->request_number)

@section('content')
@php $resultType = $request->result_type ?? \App\Enums\ResultType::PARAMETERS; @endphp
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <a aria-label="Back" title="Back" href="{{ $backRoute ?? route('admin.lab.requests.index') }}" class="text-muted me-2"><i class="ti ti-arrow-left"></i></a>
            {{ $request->request_number }}
            <span class="badge bg-{{ $resultType->color() }} ms-2"><i class="ti {{ $resultType->icon() }} me-1"></i>{{ $resultType->translatedLabel() }}</span>
        </h4>
        @if($request->targetDepartment)<small class="text-muted">{{ __('lab.department_label') }}: <strong>{{ $request->targetDepartment->name }}</strong></small>@endif
    </div>
    <div class="d-flex gap-2">
        @if(!in_array($request->status, ['completed', 'cancelled']))
        <x-confirm-form :action="route('admin.lab.requests.cancel', $request)" method="PATCH"
            :button-label="__('lab.cancel_request')" button-class="btn btn-outline-danger btn-md" icon="ti-x"
            :confirm-title="__('lab.cancel_confirm_title')" :confirm-text="__('lab.cancel_confirm_text')" :confirm-button="__('lab.cancel_confirm_button')" />
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="ti ti-check me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="ti ti-alert-circle me-1"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@php
    $pendingItems = $request->items->where('status', 'pending');
    $prepaidRequired = $request->requiresPrepaidResults();
    $resultBlocked = fn ($item) => $prepaidRequired && ! $item->isBillSettled();
@endphp

<div class="row">
    <!-- Main column -->
    <div class="col-lg-8">
        <!-- Status -->
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="fw-bold fs-5">{{ $request->request_number }}</span>
                    @if($request->targetDepartment)<small class="text-muted d-block">{{ $request->targetDepartment->name }}</small>@endif
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-{{ $request->status_color }} px-3 py-2">{{ $request->status_label }}</span>
                    <span class="badge bg-{{ $request->urgency_color }} px-3 py-2">{{ ucfirst($request->urgency) }}</span>
                </div>
            </div>
        </div>

        @php $awaitingPayment = $request->items->filter(fn ($i) => $resultBlocked($i)); @endphp
        @if($awaitingPayment->isNotEmpty())
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="ti ti-cash-off fs-4"></i>
            <div><strong>{{ __('lab.awaiting_payment_count', ['count' => $awaitingPayment->count()]) }}</strong> {{ __('lab.awaiting_payment_note') }}</div>
        </div>
        @endif

<!-- Investigation Items & Results -->
@if($resultType === \App\Enums\ResultType::PARAMETERS)
{{-- ======================= PARAMETERS (Lab Tests) ======================= --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="ti ti-flask me-1"></i>{{ __('lab.test_items_results') }}</h6>
        @if($request->status === 'processing')
        @can('lab.results.create')
        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#batchResultForm">
            <i class="ti ti-edit me-1"></i>{{ __('lab.batch_enter_results') }}
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
                                    <th>{{ __('lab.test_col') }}</th>
                                    <th>{{ __('lab.normal_range_col') }}</th>
                                    <th>{{ __('lab.result_value_col') }}</th>
                                    <th>{{ __('lab.abnormal_col') }}</th>
                                    <th>{{ __('lab.remarks_col') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($request->items as $item)
                                @if($item->status !== 'completed' && !$resultBlocked($item))
                                <tr>
                                    <td class="fw-medium">{{ $item->labTest->name ?? $item->name }} <small class="text-muted">({{ $item->labTest->code ?? '' }})</small></td>
                                    <td>
                                        @if($item->labTest?->criteria?->isNotEmpty())
                                            @foreach($item->labTest->criteria as $criterion)
                                                <div><small><strong>{{ $criterion->name }}:</strong> {{ $criterion->normal_range ?? '-' }} {{ $criterion->unit ?? '' }}</small></div>
                                            @endforeach
                                        @else
                                            <small>{{ $item->labTest->normal_range ?? '-' }} {{ $item->labTest->unit ?? '' }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="text" name="results[{{ $item->id }}][result_value]" class="form-control form-control-sm" placeholder="{{ __('lab.enter_result_placeholder') }}">
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input type="hidden" name="results[{{ $item->id }}][is_abnormal]" value="0">
                                            <input type="checkbox" name="results[{{ $item->id }}][is_abnormal]" value="1" class="form-check-input">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="results[{{ $item->id }}][remarks]" class="form-control form-control-sm" placeholder="{{ __('lab.optional_remarks') }}">
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>{{ __('lab.save_results') }}</button>
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
                        <th>{{ __('lab.hash_col') }}</th>
                        <th>{{ __('lab.test_name_col') }}</th>
                        <th>{{ __('lab.category_col') }}</th>
                        <th>{{ __('lab.normal_range') }}</th>
                        <th>{{ __('lab.status_col') }}</th>
                        <th>{{ __('lab.result_col') }}</th>
                        <th>{{ __('lab.performed_by_col') }}</th>
                        <th>{{ __('lab.verified_col') }}</th>
                        <th class="text-end">{{ __('lab.actions_col') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($request->items as $index => $item)
                    <tr class="{{ $item->result && $item->result->is_abnormal ? 'table-danger' : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td class="fw-medium">{{ $item->labTest->name ?? $item->name }} <small class="text-muted">({{ $item->labTest->code ?? '' }})</small></td>
                        <td>{{ $item->labTest?->category?->name ?? '-' }}</td>
                        <td>
                            @if($item->labTest?->criteria?->isNotEmpty())
                                @foreach($item->labTest->criteria as $criterion)
                                    <div><small><strong>{{ $criterion->name }}:</strong> {{ $criterion->normal_range ?? '-' }} {{ $criterion->unit ?? '' }}</small></div>
                                @endforeach
                            @elseif($item->service_id)
                                <small class="text-muted">{{ __('lab.see_criteria_form') }}</small>
                            @else
                                <small>{{ $item->labTest?->normal_range ?? '-' }} {{ $item->labTest?->unit ?? '' }}</small>
                            @endif
                        </td>
                        <td><span class="badge bg-{{ $item->status_color }}">{{ ucfirst($item->status) }}</span></td>
                        <td>
                            @if($item->result)
                                <span class="{{ $item->result->is_abnormal ? 'text-danger fw-bold' : '' }}">{{ $item->result->overallResultDisplay($item->service) }}</span>
                                @if($item->result->is_abnormal)<i class="ti ti-alert-triangle text-danger ms-1"></i>@endif
                                @if($item->result->remarks)<br><small class="text-muted">{{ $item->result->remarks }}</small>@endif
                            @else <span class="text-muted">-</span> @endif
                        </td>
                        <td>
                            @if($item->result)
                                <small>{{ $item->result->performedBy->name ?? '-' }}</small><br>
                                <small class="text-muted">{{ $item->result->performed_at?->format('d M H:i') }}</small>
                            @else - @endif
                        </td>
                        <td>
                            @if($item->result && $item->result->is_verified)
                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>{{ __('lab.verified_badge') }}</span>
                                <br><small class="text-muted">{{ $item->result->verifiedBy->name ?? '' }}</small>
                            @elseif($item->result) <span class="badge bg-warning">{{ __('lab.unverified_badge') }}</span>
                            @else - @endif
                        </td>
                        <td class="text-end">
                            @if($item->result)
                            <button type="button" class="btn btn-sm btn-outline-info viewResultBtn"
                                data-url="{{ route('admin.lab.results.view', $item) }}"
                                title="View Result"><i class="ti ti-eye"></i></button>
                            @endif
                            @if($item->result && !$item->result->is_verified)
                            @can('lab.results.create')
                            <form method="POST" action="{{ route('admin.lab.results.verify', $item->result) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-success" title="{{ __('lab.verify_button') }}"><i class="ti ti-check"></i></button>
                            </form>
                            @endcan
                            @endif
                            @if($item->result && $item->result->is_verified)
                            <a data-no-inertia href="{{ route('admin.lab.results.print', $item) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="{{ __('lab.print_button') }}"><i class="ti ti-printer"></i></a>
                            @endif
                            @if(in_array($request->status, ['processing']) && in_array($item->status, ['accepted','processing']) && !$item->result)
                            @can('lab.results.create')
                            @if($resultBlocked($item))
                            <span class="badge bg-warning text-dark" title="Bill not settled"><i class="ti ti-clock-dollar me-1"></i>{{ __('lab.awaiting_payment_badge') }}</span>
                            @else
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#resultModal-{{ $item->id }}" title="{{ __('lab.result_value_label') }}"><i class="ti ti-edit"></i></button>
                            @endif
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

{{-- Per-item modals (parameters) --}}
@foreach($request->items as $item)
@if(in_array($request->status, ['processing']) && in_array($item->status, ['accepted','processing']) && !$item->result && !$resultBlocked($item))
<div class="modal fade" id="resultModal-{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.lab.results.store', $item) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="result_type" value="parameters">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lab.investigation_result') }}: {{ $item->labTest->name ?? $item->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($item->labTest?->criteria?->isNotEmpty())
                <div class="alert alert-info py-2 mb-3">
                    @foreach($item->labTest->criteria as $criterion)
                        <div><small><strong>{{ $criterion->name }}:</strong> {{ $criterion->normal_range ?? '-' }} {{ $criterion->unit ?? '' }}</small></div>
                    @endforeach
                </div>
                @elseif(($item->labTest->normal_range ?? null))
                <div class="alert alert-info py-2 mb-3">
                    <small><strong>{{ __('lab.normal_range') }}:</strong> {{ $item->labTest->normal_range }} {{ $item->labTest->unit ?? '' }}</small>
                </div>
                @endif
                @php
                    $serviceCriteria = $item->service?->investigationCriteria?->where('is_active', true) ?? collect();
                    $serviceHeaders  = $item->service?->investigationHeaders?->where('is_active', true) ?? collect();
                @endphp
                @if($serviceCriteria->isNotEmpty())
                    {{-- Investigation Catalogue criteria-based result entry --}}
                    <div class="border rounded p-2 mb-3 bg-light">
                        <div class="small fw-bold text-muted text-uppercase mb-2">{{ __('lab.result_criteria') }}</div>
                        @foreach($serviceHeaders as $h)
                            @php $hCriteria = $serviceCriteria->where('header_id', $h->id); @endphp
                            @if($hCriteria->isNotEmpty())
                                <div class="mb-3">
                                    <h6 class="small fw-bold border-bottom pb-1 mb-2">{{ $h->name }}</h6>
                                    @foreach($hCriteria as $c)
                                        @include('lab._criterion_input', ['c' => $c])
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                        @php $unsorted = $serviceCriteria->whereNull('header_id'); @endphp
                        @if($unsorted->isNotEmpty())
                            <div class="mb-2">
                                @foreach($unsorted as $c)
                                    @include('lab._criterion_input', ['c' => $c])
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
                @include('lab._overall_result_input', ['item' => $item])
                <div class="mb-3">
                    <div class="form-check">
                        <input type="hidden" name="is_abnormal" value="0">
                        <input type="checkbox" name="is_abnormal" value="1" class="form-check-input" id="abnormal-{{ $item->id }}">
                        <label class="form-check-label" for="abnormal-{{ $item->id }}">{{ __('lab.mark_as_abnormal') }}</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('lab.remarks_col') }}</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="{{ __('lab.optional_remarks') }}"></textarea>
                </div>
                @include('lab._consumables', ['item' => $item])
                <hr class="my-3">
                <div class="mb-2">
                    <label class="form-label small fw-medium"><i class="ti ti-paperclip me-1"></i>{{ __('lab.attach_file_label') }} <span class="text-muted">(optional)</span></label>
                    <input type="file" name="result_file" class="form-control form-control-sm" accept="image/*,.pdf,.doc,.docx">
                    <div class="form-text">{{ __('lab.attach_file_hint') }}</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('lab.cancel_button') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('lab.save_result') }}</button>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach

{{-- Accept & bill section moved to lab/partials/accept-bill.blade.php (included
     above the result-type branches so every department shows it). --}}

@elseif($resultType === \App\Enums\ResultType::RICHTEXT)
{{-- ======================= RICHTEXT (Scan/Radiology Report) ======================= --}}
<div class="card">
    <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-align-left me-1"></i>{{ __('lab.items_reports') }}</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('lab.hash_col') }}</th>
                        <th>{{ __('lab.investigation_col') }}</th>
                        <th>{{ __('lab.status_col') }}</th>
                        <th>{{ __('lab.report_preview_col') }}</th>
                        <th>{{ __('lab.verified_col') }}</th>
                        <th class="text-end">{{ __('lab.actions_col') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($request->items as $idx => $item)
                    <tr class="{{ $item->result?->is_abnormal ? 'table-warning' : '' }}">
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-medium">{{ $item->name ?? $item->labTest?->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $item->status_color }}">{{ ucfirst($item->status) }}</span></td>
                        <td>
                            @if($item->result?->result_text)
                                <small class="text-muted">{{ Str::limit(strip_tags($item->result->result_text), 80) }}</small>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td>
                            @if($item->result?->is_verified)
                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>{{ __('lab.verified_badge') }}</span>
                            @elseif($item->result) <span class="badge bg-warning">{{ __('lab.unverified_badge') }}</span>
                            @else — @endif
                        </td>
                        <td class="text-end">
                            @if($item->result && !$item->result->is_verified)
                            @can('lab.results.create')
                            <form method="POST" action="{{ route('admin.lab.results.verify', $item->result) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-success"><i class="ti ti-check"></i> {{ __('lab.verify_button') }}</button>
                            </form>
                            @endcan
                            @endif
                            @if($request->status === 'processing' && $item->status !== 'completed')
                            @can('lab.results.create')
                            @if($resultBlocked($item))
                            <span class="badge bg-warning text-dark"><i class="ti ti-clock-dollar me-1"></i>{{ __('lab.awaiting_payment_badge') }}</span>
                            @else
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#richtextModal-{{ $item->id }}">
                                <i class="ti ti-edit me-1"></i>{{ $item->result ? __('lab.update_report') : __('lab.write_report') }} {{ __('lab.report_label') }}
                            </button>
                            @endif
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

@foreach($request->items as $item)
@if($request->status === 'processing' && $item->status !== 'completed' && !$resultBlocked($item))
<div class="modal fade" id="richtextModal-{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.lab.results.store', $item) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="result_type" value="richtext">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lab.report_label') }}: {{ $item->name ?? $item->labTest?->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('lab.findings_report') }} <span class="text-danger">*</span></label>
                    <textarea name="result_text" class="form-control" rows="12" required
                        placeholder="{{ __('lab.findings_placeholder') }}">{{ $item->result?->result_text }}</textarea>
                </div>
                <div class="row g-2">
                    <div class="col-auto">
                        <div class="form-check">
                            <input type="hidden" name="is_abnormal" value="0">
                            <input type="checkbox" name="is_abnormal" value="1" class="form-check-input" id="abn-rt-{{ $item->id }}" {{ $item->result?->is_abnormal ? 'checked' : '' }}>
                            <label class="form-check-label text-danger fw-medium" for="abn-rt-{{ $item->id }}">{{ __('lab.abnormal_finding') }}</label>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label">{{ __('lab.remarks_col') }}</label>
                    <input type="text" name="remarks" class="form-control" placeholder="{{ __('lab.optional_remark') }}" value="{{ $item->result?->remarks }}">
                </div>
                <hr class="my-3">
                <div class="mb-2">
                    <label class="form-label small fw-medium"><i class="ti ti-paperclip me-1"></i>{{ __('lab.attach_file_label') }} <span class="text-muted">(optional)</span></label>
                    <input type="file" name="result_file" class="form-control form-control-sm" accept="image/*,.pdf,.doc,.docx">
                    <div class="form-text">{{ __('lab.attach_file_hint') }}</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('lab.cancel_button') }}</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-send me-1"></i>{{ __('lab.submit_report') }}</button>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach

@else
{{-- ======================= IMAGE / DOCUMENT ======================= --}}
<div class="card">
    <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti {{ $resultType->icon() }} me-1"></i>{{ __('lab.items_files') }}</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('lab.hash_col') }}</th>
                        <th>{{ __('lab.investigation_col') }}</th>
                        <th>{{ __('lab.status_col') }}</th>
                        <th>{{ __('lab.file_col') }}</th>
                        <th>{{ __('lab.verified_col') }}</th>
                        <th class="text-end">{{ __('lab.actions_col') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($request->items as $idx => $item)
                    <tr class="{{ $item->result?->is_abnormal ? 'table-warning' : '' }}">
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-medium">{{ $item->name ?? $item->labTest?->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $item->status_color }}">{{ ucfirst($item->status) }}</span></td>
                        <td>
                            @if($item->result?->result_file)
                                <a href="{{ Storage::url($item->result->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-download me-1"></i>{{ $item->result->result_file_name ?? 'View File' }}
                                </a>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td>
                            @if($item->result?->is_verified)
                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>{{ __('lab.verified_badge') }}</span>
                            @elseif($item->result) <span class="badge bg-warning">{{ __('lab.unverified_badge') }}</span>
                            @else — @endif
                        </td>
                        <td class="text-end">
                            @if($item->result && !$item->result->is_verified)
                            @can('lab.results.create')
                            <form method="POST" action="{{ route('admin.lab.results.verify', $item->result) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-success"><i class="ti ti-check"></i> {{ __('lab.verify_button') }}</button>
                            </form>
                            @endcan
                            @endif
                            @if($request->status === 'processing' && $item->status !== 'completed')
                            @can('lab.results.create')
                            @if($resultBlocked($item))
                            <span class="badge bg-warning text-dark"><i class="ti ti-clock-dollar me-1"></i>{{ __('lab.awaiting_payment_badge') }}</span>
                            @else
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#fileModal-{{ $item->id }}">
                                <i class="ti ti-upload me-1"></i>{{ __('lab.upload_button') }}
                            </button>
                            @endif
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

@foreach($request->items as $item)
@if($request->status === 'processing' && $item->status !== 'completed' && !$resultBlocked($item))
<div class="modal fade" id="fileModal-{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.lab.results.store', $item) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="result_type" value="{{ $resultType->value }}">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('lab.upload_title') }}: {{ $item->name ?? $item->labTest?->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">
                        @if($resultType === \App\Enums\ResultType::IMAGE) {{ __('lab.image_file') }} @else {{ __('lab.document_file') }} @endif
                        <span class="text-danger">*</span>
                    </label>
                    <input type="file" name="result_file" class="form-control" required
                        accept="{{ $resultType === \App\Enums\ResultType::IMAGE ? 'image/*' : '.pdf,.doc,.docx' }}">
                    <div class="form-text">
                        @if($resultType === \App\Enums\ResultType::IMAGE) {{ __('lab.image_hint') }}
                        @else {{ __('lab.document_hint') }} @endif
                    </div>
                </div>
                <div class="mb-2">
                    <div class="form-check">
                        <input type="hidden" name="is_abnormal" value="0">
                        <input type="checkbox" name="is_abnormal" value="1" class="form-check-input" id="abn-f-{{ $item->id }}">
                        <label class="form-check-label text-danger" for="abn-f-{{ $item->id }}">{{ __('lab.abnormal_finding') }}</label>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label">{{ __('lab.remarks_col') }}</label>
                    <input type="text" name="remarks" class="form-control" placeholder="{{ __('lab.optional_remark') }}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('lab.cancel_button') }}</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-upload me-1"></i>{{ __('lab.upload_button') }}</button>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach
@endif


{{-- Patient / Visit / Doctor / Progress moved to the right sidebar below --}}

{{-- Accept & bill pending items — available for EVERY investigation department
     (lab, radiology, scan, …), not only parameter/lab-test requests. --}}
@include('lab.partials.accept-bill')

    </div>{{-- /col-lg-8 --}}

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Patient / Recipient -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-user me-1"></i>{{ $request->patient ? __('lab.patient_label') : __('lab.recipient_label') }}</h6></div>
            <div class="card-body">
                @if($request->patient)
                    <h6 class="fw-bold">{{ $request->patient->full_name }}</h6>
                    <small class="text-muted d-block">{{ $request->patient->patient_number }}</small>
                    <small class="text-muted d-block">{{ $request->patient->age }}y &middot; {{ $request->patient->gender?->value }}</small>
                    @if($request->patient->allergies)
                    <div class="alert alert-danger py-1 mt-2 mb-0"><small><strong>{{ __('lab.allergies') }}:</strong> {{ $request->patient->allergies }}</small></div>
                    @endif
                @else
                    <h6 class="fw-bold">{{ $request->external_party_name ?? '—' }}</h6>
                    @if($request->external_party_sex || $request->external_party_age)
                        <small class="text-muted d-block">{{ $request->external_party_sex }}@if($request->external_party_age) &middot; {{ $request->external_party_age }}y @endif</small>
                    @endif
                    <small class="text-muted d-block">{{ __('lab.external_walk_in') }}</small>
                    @if($request->external_party_contact)<small class="text-muted d-block">{{ $request->external_party_contact }}</small>@endif
                @endif
            </div>
        </div>

        <!-- Visit -->
        @if($request->visit)
        <div class="card mb-3">
            <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-calendar-check me-1"></i>{{ __('lab.visit_label') }}</h6></div>
            <div class="card-body">
                <a href="{{ route('admin.visits.show', $request->visit) }}" class="fw-medium">{{ $request->visit->visit_number }}</a>
                <small class="text-muted d-block">{{ $request->visit->visit_date?->format('d M Y') }}</small>
                <x-status-badge :status="$request->visit->status" />
            </div>
        </div>
        @endif

        <!-- Requested By -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('lab.requested_by_label') }}</h6></div>
            <div class="card-body">
                <h6 class="fw-medium mb-0">{{ $request->requestedBy->name ?? '—' }}</h6>
                <small class="text-muted">{{ $request->department->name ?? '—' }}</small>
                <small class="text-muted d-block mt-1"><i class="ti ti-clock me-1"></i>{{ $request->created_at->format('d M Y H:i') }}</small>
            </div>
        </div>

        <!-- Progress -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-chart-bar me-1"></i>{{ __('lab.progress_label') }}</h6></div>
            <div class="card-body text-center">
                <h1 class="mb-1 {{ $request->completion_percentage === 100 ? 'text-success' : 'text-primary' }}">{{ $request->completion_percentage }}%</h1>
                <div class="progress mb-2" style="height: 10px;"><div class="progress-bar bg-success" style="width: {{ $request->completion_percentage }}%"></div></div>
                <small class="text-muted">{{ __('lab.tests_completed', ['done' => $request->items->where('status', 'completed')->count(), 'total' => $request->items->count()]) }}</small>
            </div>
        </div>

        @if($request->clinical_info)
        <!-- Clinical Information -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-notes me-1"></i>{{ __('lab.clinical_information') }}</h6></div>
            <div class="card-body"><p class="mb-0">{{ $request->clinical_info }}</p></div>
        </div>
        @endif
    </div>{{-- /col-lg-4 --}}
</div>{{-- /row --}}

{{-- View Result Modal (lazy-loaded via fetch) --}}
<div class="modal fade" id="viewResultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-clipboard-data me-1"></i>{{ __('lab.investigation_result') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewResultBody">
                <div class="text-center py-4 text-muted"><div class="spinner-border"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('lab.close_button') }}</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.viewResultBtn');
    if (!btn) return;
    const modal = new bootstrap.Modal(document.getElementById('viewResultModal'));
    const body  = document.getElementById('viewResultBody');
    body.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border"></div></div>';
    modal.show();
    try {
        const r = await fetch(btn.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        body.innerHTML = await r.text();
    } catch (err) {
        body.innerHTML = '<div class="alert alert-danger">' + err.message + '</div>';
    }
});
</script>
@endpush
@endsection
