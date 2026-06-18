@extends('layouts.app')

@section('title', __('sms.delivery_reports'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('sms.delivery_reports')" :description="__('sms.sms_gateway')" icon="ti-report" />

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent"><strong>{{ __('sms.delivery_reports') }}</strong></div>
            <div class="card-body p-0">
                @if($reports->isEmpty())
                    <x-empty-state icon="ti-report" :message="__('sms.no_reports')" />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('sms.provider_message_id') }}</th>
                                    <th>{{ __('integrations.provider') }}</th>
                                    <th>{{ __('sms.status') }}</th>
                                    <th>{{ __('sms.created_at') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reports as $report)
                                    <tr>
                                        <td class="small">{{ $report->provider_message_id ?? '—' }}</td>
                                        <td>{{ $report->provider?->name ?? '—' }}</td>
                                        <td><x-status-badge :status="$report->status" domain="sms_recipient" size="sm" /></td>
                                        <td class="small text-muted">{{ $report->created_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="card-footer bg-transparent">{{ $reports->links() }}</div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent"><strong>{{ __('sms.provider_callbacks') }}</strong></div>
            <div class="card-body p-0">
                @if($callbacks->isEmpty())
                    <x-empty-state icon="ti-webhook" :message="__('sms.no_reports')" />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('integrations.provider_code') }}</th>
                                    <th>{{ __('sms.type') }}</th>
                                    <th>{{ __('sms.provider_message_id') }}</th>
                                    <th>{{ __('payments.gateway.processed') }}</th>
                                    <th>{{ __('sms.created_at') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($callbacks as $callback)
                                    <tr>
                                        <td><code>{{ $callback->provider_code }}</code></td>
                                        <td>{{ $callback->event_type ?? '—' }}</td>
                                        <td class="small">{{ $callback->provider_message_id ?? '—' }}</td>
                                        <td>
                                            @if($callback->processed)
                                                <span class="badge bg-success">{{ __('payments.gateway.processed') }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark">—</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $callback->created_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="card-footer bg-transparent">{{ $callbacks->links() }}</div>
        </div>
    </div>
</div>
@endsection
