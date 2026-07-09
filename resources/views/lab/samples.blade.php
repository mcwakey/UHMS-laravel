@extends('layouts.app')
@section('title', __('samples.queue_title'))

@section('content')
<!-- Page Header -->
<div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-test-pipe me-2"></i>{{ __('samples.queue_title') }}</h4>
        <small class="text-muted">{{ __('samples.queue_subtitle') }}</small>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="ti ti-check me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="ti ti-alert-circle me-1"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<!-- Stats Cards -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-warning"><div class="card-body py-3 text-center">
            <h3 class="mb-0 text-warning">{{ $stats['pending'] }}</h3>
            <small class="text-muted">{{ __('samples.stat_pending') }}</small>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card border-info"><div class="card-body py-3 text-center">
            <h3 class="mb-0 text-info">{{ $stats['collected'] }}</h3>
            <small class="text-muted">{{ __('samples.stat_collected') }}</small>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card border-success"><div class="card-body py-3 text-center">
            <h3 class="mb-0 text-success">{{ $stats['received'] }}</h3>
            <small class="text-muted">{{ __('samples.stat_received_today') }}</small>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger"><div class="card-body py-3 text-center">
            <h3 class="mb-0 text-danger">{{ $stats['rejected'] }}</h3>
            <small class="text-muted">{{ __('samples.stat_rejected') }}</small>
        </div></div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">{{ __('common.search') }}</label>
                <input type="text" name="search" class="form-control" placeholder="{{ __('samples.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('common.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('samples.all_status') }}</option>
                    @foreach(\App\Enums\SampleStatus::selectableCases() as $case)
                        <option value="{{ $case->value }}" {{ request('status') === $case->value ? 'selected' : '' }}>{{ $case->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('samples.specimen_col') }}</label>
                <select name="specimen_type" class="form-select">
                    <option value="">{{ __('samples.all_specimens') }}</option>
                    @foreach($specimenTypes as $code => $def)
                        <option value="{{ $code }}" {{ request('specimen_type') === $code ? 'selected' : '' }}>
                            {{ \Illuminate\Support\Facades\Lang::has('samples.specimen.'.$code) ? __('samples.specimen.'.$code) : ($def['label'] ?? $code) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">{{ __('common.department') }}</label>
                <select name="department_id" class="form-select">
                    <option value="">{{ __('lab.all_departments') }}</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <div class="flex-grow-1">
                    <label class="form-label small">{{ __('common.from') }}</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="flex-grow-1">
                    <label class="form-label small">{{ __('common.to') }}</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>{{ __('common.filter') }}</button>
                <a href="{{ route('admin.lab.samples.index') }}" class="btn btn-outline-secondary btn-md"><i class="ti ti-x me-1"></i>{{ __('common.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<!-- Samples Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('samples.sample_col') }}</th>
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('samples.specimen_col') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('samples.status_col') }}</th>
                        <th>{{ __('common.date') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($samples as $sample)
                    @php $st = $sample->status_enum; @endphp
                    <tr>
                        <td>
                            <span class="fw-medium">{{ $sample->sample_number }}</span>
                            @if($sample->barcode && $sample->barcode !== $sample->sample_number)
                                <small class="text-muted d-block"><i class="ti ti-barcode me-1"></i>{{ $sample->barcode }}</small>
                            @endif
                        </td>
                        <td>
                            <div class="fw-medium">{{ $sample->labRequest?->partyName() ?? '—' }}</div>
                            <small class="text-muted">{{ $sample->labRequest?->request_number }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $sample->specimenConfig()['color'] ?? 'secondary' }}-subtle text-{{ $sample->specimenConfig()['color'] ?? 'secondary' }}">
                                <i class="ti {{ $sample->specimenConfig()['icon'] ?? 'ti-flask' }} me-1"></i>{{ $sample->specimenLabel() }}
                            </span>
                            <small class="text-muted d-block">{{ $sample->items->count() }} {{ __('samples.items_col') }}</small>
                        </td>
                        <td>{{ $sample->labRequest?->targetDepartment?->name ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $st->color() }}"><i class="ti {{ $st->icon() }} me-1"></i>{{ $st->translatedLabel() }}</span>
                            @if($st === \App\Enums\SampleStatus::REJECTED && $sample->rejection_reason)
                                <small class="text-danger d-block">{{ $sample->rejection_reason }}</small>
                            @endif
                        </td>
                        <td>
                            <small>{{ $sample->created_at->translatedFormat('d M Y') }}</small><br>
                            <small class="text-muted">{{ $sample->created_at->format('H:i') }}</small>
                        </td>
                        <td class="text-end text-nowrap">
                            @if(in_array($st, [\App\Enums\SampleStatus::PENDING, \App\Enums\SampleStatus::REJECTED], true))
                                @can('lab.samples.collect')
                                <x-confirm-form :action="route('admin.lab.samples.collect', $sample)" method="PATCH"
                                    button-class="btn btn-sm btn-outline-info" icon="ti-droplet" :button-label="''"
                                    :confirm-title="__('samples.collect_confirm_title')" :confirm-text="__('samples.collect_confirm_text', ['number' => $sample->sample_number])"
                                    :confirm-button="__('samples.mark_collected_btn')" />
                                @endcan
                            @endif
                            @if($st === \App\Enums\SampleStatus::COLLECTED)
                                @can('lab.samples.receive')
                                <x-confirm-form :action="route('admin.lab.samples.receive', $sample)" method="PATCH"
                                    button-class="btn btn-sm btn-outline-success" icon="ti-check" :button-label="''"
                                    :confirm-title="__('samples.receive_confirm_title')" :confirm-text="__('samples.receive_confirm_text', ['number' => $sample->sample_number])"
                                    :confirm-button="__('samples.receive_btn')" />
                                @endcan
                            @endif
                            @if(! $st->isTerminal())
                                @can('lab.samples.manage')
                                <x-confirm-form :action="route('admin.lab.samples.reject', $sample)" method="PATCH"
                                    button-class="btn btn-sm btn-outline-danger" icon="ti-x" :button-label="''" require-reason reason-name="rejection_reason"
                                    :reason-placeholder="__('samples.rejection_reason_label')"
                                    :confirm-title="__('samples.reject_title')" :confirm-text="__('samples.reject_confirm_text', ['number' => $sample->sample_number])"
                                    :confirm-button="__('samples.reject_btn')" />
                                @endcan
                            @endif
                            @if($st === \App\Enums\SampleStatus::RECEIVED)
                                @can('lab.samples.manage')
                                <x-confirm-form :action="route('admin.lab.samples.dispose', $sample)" method="PATCH"
                                    button-class="btn btn-sm btn-outline-secondary" icon="ti-trash" :button-label="''"
                                    :confirm-title="__('samples.dispose_confirm_title')" :confirm-text="__('samples.dispose_confirm_text', ['number' => $sample->sample_number])"
                                    :confirm-button="__('samples.dispose_btn')" />
                                @endcan
                            @endif
                            @if($sample->labRequest)
                            <a href="{{ route('admin.lab.requests.show', $sample->labRequest) }}" class="btn btn-sm btn-outline-primary" title="{{ __('samples.view_request') }}">
                                <i class="ti ti-external-link"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="ti ti-test-pipe-off fs-1 d-block mb-2"></i>
                            {{ __('samples.none_found') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($samples->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $samples->links() }}
</div>
@endif
@endsection
