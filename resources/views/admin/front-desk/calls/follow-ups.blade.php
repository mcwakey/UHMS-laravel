@extends('layouts.app')
@section('title', __('front_desk.calls.callback_queue'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('front_desk.calls.callback_queue') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ $logs->total() }}</span>
        </h4>
    </div>
    <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-list me-1"></i>{{ __('front_desk.calls.title') }}</a>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.follow-ups') }}" class="btn btn-sm {{ empty(array_filter($filters)) ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('front_desk.dashboard.pending_callbacks') }}</a>
    <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.follow-ups', ['overdue' => 1]) }}" class="btn btn-sm {{ !empty($filters['overdue']) ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('front_desk.filters.overdue') }}</a>
    <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.follow-ups', ['due_today' => 1]) }}" class="btn btn-sm {{ !empty($filters['due_today']) ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('front_desk.filters.due_today') }}</a>
    <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.follow-ups', ['mine' => 1]) }}" class="btn btn-sm {{ !empty($filters['mine']) ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('front_desk.filters.mine') }}</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('front_desk.fields.category') }}</th>
                        <th>{{ __('front_desk.fields.phone_number') }}</th>
                        <th>{{ __('front_desk.fields.assigned_to') }}</th>
                        <th>{{ __('front_desk.fields.follow_up_due_at') }}</th>
                        <th>{{ __('front_desk.fields.department') }}</th>
                        <th class="text-end">{{ __('front_desk.actions.view') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="fw-medium">{{ $log->category?->translatedLabel() }}</td>
                        <td>{{ $log->phone_number ?? __('front_desk.none') }}</td>
                        <td>{{ $log->assignedFollowUpUser?->full_name ?? __('front_desk.none') }}</td>
                        <td>
                            {{ $log->follow_up_due_at?->format('d M Y H:i') ?? __('front_desk.calls.no_due_date') }}
                            @if($log->isOverdueCallback())<span class="badge bg-danger ms-1">{{ __('front_desk.filters.overdue') }}</span>@endif
                        </td>
                        <td>{{ $log->department?->name ?? __('front_desk.none') }}</td>
                        <td class="text-end">
                            <a href="{{ $workspaceRoutes->route('admin.front-desk.calls.show', $log) }}" class="btn btn-sm btn-icon btn-outline-primary" title="{{ __('front_desk.actions.view') }}"><i class="ti ti-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6"><x-empty-state icon="ti-phone-call" :message="__('front_desk.calls.no_callbacks')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
@endsection
