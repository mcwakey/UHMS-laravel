@extends('layouts.app')
@section('title', __('admissions.admission_requests'))

@section('content')
<x-page-header :title="__('admissions.admission_requests')" icon="ti-bed" :description="__('admissions.requests_description')">
    @if($totalPending > 0)
        <span class="badge bg-warning text-dark fw-medium border py-1 px-2 border-warning fs-13 ms-1">{{ __('admissions.pending_count', ['count' => $totalPending]) }}</span>
    @else
        <span class="badge badge-soft-secondary fw-medium border py-1 px-2 fs-13 ms-1">{{ __('admissions.zero_pending') }}</span>
    @endif
    <x-slot:actions>
        <a href="{{ route('admin.admissions.index') }}" class="btn btn-outline-secondary btn-md fs-13">
            <i class="ti ti-bed me-1"></i>{{ __('admissions.all_admissions') }}
        </a>
        @can('ward.admit')
        <a href="{{ route('admin.admissions.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>{{ __('admissions.new_admission') }}
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.admissions.requests') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control"
                       placeholder="{{ __('admissions.search_requests_ph') }}"
                       value="{{ $searchQuery }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md">
                    <i class="ti ti-search me-1"></i>{{ __('common.search') }}
                </button>
                @if($searchQuery)
                    <a href="{{ route('admin.admissions.requests') }}" class="btn btn-outline-secondary btn-md ms-1">
                        <i class="ti ti-x me-1"></i>{{ __('common.clear') }}
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if($visits->isEmpty())
            <x-empty-state icon="ti-circle-check" :title="__('admissions.no_pending_requests')" :message="__('admissions.no_pending_detail')" />
        @else
            <div class="table-responsive">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('admissions.patient') }}</th>
                            <th>{{ __('admissions.visit_no') }}</th>
                            <th>{{ __('admissions.type_col') }}</th>
                            <th>{{ __('admissions.department') }}</th>
                            <th>{{ __('admissions.doctor') }}</th>
                            <th>{{ __('admissions.waiting_since') }}</th>
                            <th class="text-end pe-3">{{ __('admissions.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($visits as $visit)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-sm bg-warning bg-opacity-15 rounded-circle flex-shrink-0">
                                            <span class="fs-12 fw-bold text-warning">
                                                {{ strtoupper(substr($visit->patient?->full_name ?? '?', 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $visit->patient?->full_name ?? '—' }}</div>
                                            <small class="text-muted">{{ $visit->patient?->patient_number ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted fs-13">{{ $visit->visit_number ?? '—' }}</span>
                                </td>
                                <td>
                                    @if($visit->visit_type)
                                        <span class="badge badge-soft-info">{{ $visit->visit_type->label() }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted fs-13">{{ $visit->department?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted fs-13">
                                        {{ $visit->activeConsultationRoute?->doctor?->full_name ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $since = $visit->updated_at;
                                        $diff  = $since?->diffForHumans() ?? '—';
                                        $isUrgent = $since && $since->diffInHours(now()) >= 2;
                                    @endphp
                                    <span class="fs-13 {{ $isUrgent ? 'text-danger fw-semibold' : 'text-muted' }}"
                                          title="{{ $since?->format('d M Y H:i') }}">
                                        {{ $diff }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    @can('ward.admit')
                                    <a href="{{ route('admin.admissions.create', ['visit_id' => $visit->id]) }}"
                                       class="btn btn-sm btn-warning fw-semibold">
                                        <i class="ti ti-bed me-1"></i>{{ __('admissions.admit_now') }}
                                    </a>
                                    @else
                                    <span class="text-muted small">{{ __('admissions.no_permission') }}</span>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($visits->hasPages())
                <div class="px-3 py-2 border-top">
                    {{ $visits->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
