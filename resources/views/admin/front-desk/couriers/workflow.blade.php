@extends('layouts.app')
@section('title', __('front_desk.couriers.workflow'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('front_desk.couriers.workflow') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ $logs->total() }}</span>
        </h4>
    </div>
    <a href="{{ route('admin.front-desk.couriers.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-list me-1"></i>{{ __('front_desk.couriers.title') }}</a>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach($quickFilters as $qf)
    <a href="{{ route('admin.front-desk.couriers.workflow', ['quick' => $qf]) }}"
       class="btn btn-sm {{ ($filters['quick'] ?? 'pending_dispatch') === $qf ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('front_desk.courier_quick.' . $qf) }}</a>
    @endforeach
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('front_desk.fields.courier_type') }}</th>
                        <th>{{ __('front_desk.fields.direction') }}</th>
                        <th>{{ __('front_desk.fields.recipient_name') }}</th>
                        <th>{{ __('front_desk.fields.status') }}</th>
                        <th>{{ __('front_desk.fields.handover_status') }}</th>
                        <th>{{ __('front_desk.fields.received_or_sent_at') }}</th>
                        <th class="text-end">{{ __('front_desk.actions.view') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="fw-medium">{{ $log->courier_type?->translatedLabel() }}</td>
                        <td><x-status-badge :status="$log->direction" size="sm" soft /></td>
                        <td>{{ $log->recipient_name ?? ($log->recipientDepartment?->name ?? __('front_desk.none')) }}</td>
                        <td><x-status-badge :status="$log->status" size="sm" /></td>
                        <td>
                            @if($log->handover_status)<x-status-badge :status="$log->handover_status" size="sm" soft />@else {{ __('front_desk.none') }} @endif
                            @if($log->isOverdueCourier())<span class="badge bg-danger ms-1">{{ __('front_desk.filters.overdue') }}</span>@endif
                        </td>
                        <td>{{ $log->received_or_sent_at?->format('d M Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.front-desk.couriers.show', $log) }}" class="btn btn-sm btn-icon btn-outline-primary" title="{{ __('front_desk.actions.view') }}"><i class="ti ti-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><x-empty-state icon="ti-truck-delivery" :message="__('front_desk.couriers.no_workflow_items')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
@endsection
