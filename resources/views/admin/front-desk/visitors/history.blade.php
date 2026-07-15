@extends('layouts.app')
@section('title', __('front_desk.visitors.history'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ $heading }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ $logs->total() }}</span>
        </h4>
    </div>
    <a href="{{ $backRoute }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('front_desk.actions.back') }}</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('front_desk.fields.visitor_name') }}</th>
                        <th>{{ __('front_desk.fields.relationship_to_patient') }}</th>
                        <th>{{ __('front_desk.fields.purpose') }}</th>
                        <th>{{ __('front_desk.fields.badge_number') }}</th>
                        <th>{{ __('front_desk.fields.time_in') }}</th>
                        <th>{{ __('front_desk.fields.time_out') }}</th>
                        <th>{{ __('front_desk.fields.status') }}</th>
                        <th>{{ __('front_desk.fields.checked_in_by') }}</th>
                        <th class="text-end">{{ __('front_desk.actions.view') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="fw-medium">{{ $log->visitor_name }}</td>
                        <td>{{ $log->relationship_to_patient ?? __('front_desk.none') }}</td>
                        <td>{{ Str::limit($log->purpose, 30) ?: __('front_desk.none') }}</td>
                        <td>{{ $log->badge_number ?? __('front_desk.none') }}</td>
                        <td>{{ $log->time_in?->format('d M Y H:i') }}</td>
                        <td>{{ $log->time_out?->format('d M Y H:i') ?? __('front_desk.none') }}</td>
                        <td>
                            <x-status-badge :status="$log->status" size="sm" />
                            @if($log->isOverdue())<span class="badge bg-danger ms-1">{{ __('front_desk.visitors.overdue') }}</span>@endif
                        </td>
                        <td>{{ $log->checkedInBy?->full_name ?? __('front_desk.none') }}</td>
                        <td class="text-end">
                            <a href="{{ $workspaceRoutes->route('admin.front-desk.visitors.show', $log) }}" class="btn btn-sm btn-icon btn-outline-primary" title="{{ __('front_desk.actions.view') }}"><i class="ti ti-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9"><x-empty-state icon="ti-users" :message="__('front_desk.visitors.none')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
@endsection
