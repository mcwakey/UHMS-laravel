@extends('layouts.app')
@section('title', __('admissions.admission_requests'))

@section('content')
<x-page-header :title="__('admissions.admission_requests')" icon="ti-bed" :description="__('admissions.requests_description')">
    <x-slot:actions>
        @can('admission.requests.create')
        <a href="{{ $workspaceRoutes->route('admin.admissions.requests.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>{{ __('admissions.new_request') }}
        </a>
        @endcan
        <a href="{{ $workspaceRoutes->route('admin.admissions.index') }}" class="btn btn-outline-secondary btn-md fs-13">
            <i class="ti ti-bed me-1"></i>{{ __('admissions.all_admissions') }}
        </a>
    </x-slot:actions>
</x-page-header>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-circle-check me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<x-filter-bar
    :action="$workspaceRoutes->route('admin.admissions.requests')"
    :reset-url="$workspaceRoutes->route('admin.admissions.requests')"
    ajax
    ajax-target="#admissionRequestsResults"
>
    <input type="hidden" name="per_page" value="{{ request('per_page', $admissionRequests->perPage()) }}" data-filter-per-page-input>

    <div class="col-md-4">
        <label class="form-label small">{{ __('common.search') }}</label>
        <input type="text" name="search" class="form-control" placeholder="{{ __('admissions.search_requests_ph') }}" value="{{ $searchQuery }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small">{{ __('admissions.status') }}</label>
        <select name="status" class="form-select">
            <option value="">{{ __('admissions.all_status') }}</option>
            @foreach($statuses as $status)
                <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">{{ __('admissions.source') }}</label>
        <select name="source_type" class="form-select">
            <option value="">{{ __('admissions.all_sources') }}</option>
            @foreach($sources as $source)
                <option value="{{ $source->value }}" @selected($selectedSource === $source->value)>{{ $source->label() }}</option>
            @endforeach
        </select>
    </div>
    <x-slot:actions>
        <a aria-label="{{ __('common.reset') }}" title="{{ __('common.reset') }}" href="{{ $workspaceRoutes->route('admin.admissions.requests') }}" class="btn btn-outline-secondary btn-icon" data-filter-reset>
            <i class="ti ti-x"></i>
        </a>
    </x-slot:actions>
</x-filter-bar>

<div id="admissionRequestsResults">
<x-data-table
    id="admissionRequestsDataTable"
    :paginator="$admissionRequests"
    show-summary
    show-per-page
    :current-per-page="request('per_page', $admissionRequests->perPage())"
    :per-page-options="[10, 20, 25, 50, 100]"
>
    <x-slot:head>
        <tr>
            <th class="ps-3">{{ __('admissions.patient') }}</th>
            <th>{{ __('admissions.source') }}</th>
            <th>{{ __('admissions.requested_ward') }}</th>
            <th>{{ __('admissions.priority') }}</th>
            <th>{{ __('admissions.status') }}</th>
            <th>{{ __('admissions.requested_by') }}</th>
            <th>{{ __('admissions.requested_at') }}</th>
            <th class="text-end pe-3">{{ __('admissions.actions') }}</th>
        </tr>
    </x-slot:head>

    @foreach($admissionRequests as $admissionRequest)
        <tr>
            <td class="ps-3">
                <div class="fw-semibold">{{ $admissionRequest->patient?->full_name ?? '-' }}</div>
                <small class="text-muted">
                    {{ $admissionRequest->patient?->patient_number ?? '' }}
                    @if($admissionRequest->visit)
                        &middot; {{ $admissionRequest->visit->visit_number }}
                    @endif
                </small>
            </td>
            <td>
                <span class="badge badge-soft-info">{{ $admissionRequest->source_type->label() }}</span>
            </td>
            <td>{{ $admissionRequest->requestedWard?->name ?? '-' }}</td>
            <td>
                @if($admissionRequest->priority)
                    @php($priorityKey = 'admissions.priorities.' . strtolower($admissionRequest->priority))
                    {{ \Illuminate\Support\Facades\Lang::has($priorityKey) ? __($priorityKey) : ucfirst(strtolower(str_replace('_', ' ', $admissionRequest->priority))) }}
                @else
                    -
                @endif
            </td>
            <td>
                <span class="badge bg-{{ $admissionRequest->status->color() }}">{{ $admissionRequest->status->label() }}</span>
            </td>
            <td>{{ $admissionRequest->requestedBy?->full_name ?? '-' }}</td>
            <td>
                <span title="{{ $admissionRequest->requested_at?->format('d M Y H:i') }}">
                    {{ $admissionRequest->requested_at?->diffForHumans() ?? '-' }}
                </span>
            </td>
            <td class="text-end pe-3">
                <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                    <a href="{{ $workspaceRoutes->route('admin.admissions.requests.show', $admissionRequest) }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-eye"></i>
                    </a>
                    @if($admissionRequest->status === \App\Enums\AdmissionRequestStatus::REQUESTED)
                        @can('admission.requests.accept')
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.requests.accept', $admissionRequest) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-success" title="{{ __('admissions.accept_request') }}">
                                <i class="ti ti-check"></i>
                            </button>
                        </form>
                        @endcan
                    @endif
                    @if($admissionRequest->status->canConvert())
                        @can('admission.requests.convert')
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.requests.convert', $admissionRequest) }}">
                            @csrf
                            <button class="btn btn-sm btn-warning" title="{{ __('admissions.convert_to_admission') }}">
                                <i class="ti ti-bed"></i>
                            </button>
                        </form>
                        @endcan
                    @endif
                </div>
            </td>
        </tr>
    @endforeach

    @foreach($legacyVisits as $visit)
        <tr class="table-warning">
            <td class="ps-3">
                <div class="fw-semibold">{{ $visit->patient?->full_name ?? '-' }}</div>
                <small class="text-muted">{{ $visit->patient?->patient_number ?? '' }} &middot; {{ $visit->visit_number }}</small>
            </td>
            <td><span class="badge badge-soft-secondary">{{ __('admissions.legacy_visit_source') }}</span></td>
            <td>-</td>
            <td>-</td>
            <td><span class="badge bg-warning text-dark">{{ __('admissions.legacy_admitting') }}</span></td>
            <td>{{ $visit->activeConsultationRoute?->doctor?->full_name ?? '-' }}</td>
            <td>{{ $visit->updated_at?->diffForHumans() ?? '-' }}</td>
            <td class="text-end pe-3">
                <div class="d-inline-flex gap-1">
                    @can('admission.requests.create')
                    <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.requests.store') }}">
                        @csrf
                        <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                        <input type="hidden" name="source_type" value="direct">
                        <input type="hidden" name="clinical_summary" value="{{ $visit->chief_complaint }}">
                        <button class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-git-branch me-1"></i>{{ __('admissions.create_request') }}
                        </button>
                    </form>
                    @endcan
                    @can('ward.admit')
                    <a href="{{ $workspaceRoutes->route('admin.admissions.create', ['visit_id' => $visit->id]) }}" class="btn btn-sm btn-warning">
                        <i class="ti ti-bed me-1"></i>{{ __('admissions.admit_now') }}
                    </a>
                    @endcan
                </div>
            </td>
        </tr>
    @endforeach

    @if($admissionRequests->isEmpty() && $legacyVisits->isEmpty())
        <tr>
            <td colspan="8" class="text-center py-4 text-muted">
                <i class="ti ti-circle-check fs-1 d-block mb-2"></i>
                <div class="fw-semibold">{{ __('admissions.no_pending_requests') }}</div>
                <div class="small">{{ __('admissions.no_pending_detail') }}</div>
            </td>
        </tr>
    @endif
</x-data-table>
</div>
@endsection
