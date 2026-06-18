@extends('layouts.app')

@section('title', __('payments.gateway.payment_callbacks'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('payments.gateway.payment_callbacks')" :description="__('payments.gateway.payment_gateway')" icon="ti-webhook" />

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($callbacks->isEmpty())
                    <x-empty-state icon="ti-webhook" :message="__('payments.gateway.no_callbacks')" />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('integrations.provider_code') }}</th>
                                    <th>{{ __('sms.type') }}</th>
                                    <th>{{ __('payments.gateway.payment_reference') }}</th>
                                    <th>{{ __('payments.gateway.signature_valid') }}</th>
                                    <th>{{ __('payments.gateway.processed') }}</th>
                                    <th>{{ __('sms.created_at') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($callbacks as $callback)
                                    <tr>
                                        <td><code>{{ $callback->provider_code }}</code></td>
                                        <td>{{ $callback->event_type ?? '—' }}</td>
                                        <td class="small">{{ $callback->payment_reference ?? '—' }}</td>
                                        <td>
                                            @if($callback->signature_valid)
                                                <i class="ti ti-shield-check text-success"></i>
                                            @else
                                                <i class="ti ti-shield-off text-muted"></i>
                                            @endif
                                        </td>
                                        <td>
                                            @if($callback->processed)
                                                <span class="badge bg-success">{{ __('payments.gateway.processed') }}</span>
                                                @if($callback->processing_error)<span class="badge bg-warning text-dark ms-1">{{ $callback->processing_error }}</span>@endif
                                            @else
                                                <span class="badge bg-secondary">—</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $callback->created_at?->format('Y-m-d H:i:s') }}</td>
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
