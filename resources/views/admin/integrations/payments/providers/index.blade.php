@extends('layouts.app')

@section('title', __('payments.gateway.payment_providers'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('payments.gateway.payment_gateway')" :description="__('payments.gateway.payment_providers')" icon="ti-credit-card">
            <x-slot:actions>
                @can('integrations.payments.providers.manage')
                    <a href="{{ route('admin.integrations.payments.providers.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>{{ __('integrations.add_provider') }}
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($providers->isEmpty())
                    <x-empty-state icon="ti-credit-card" :title="__('integrations.no_active_provider')" :message="__('payments.gateway.no_transactions')" />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('integrations.name') }}</th>
                                    <th>{{ __('integrations.provider_code') }}</th>
                                    <th>{{ __('integrations.environment') }}</th>
                                    <th>{{ __('integrations.status') }}</th>
                                    <th>{{ __('integrations.active_provider') }}</th>
                                    <th>{{ __('integrations.last_test_result') }}</th>
                                    <th class="text-end">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($providers as $provider)
                                    <tr>
                                        <td class="fw-semibold">{{ $provider->name }}</td>
                                        <td><code>{{ $provider->code }}</code></td>
                                        <td><x-status-badge :status="$provider->environment" domain="default" soft /></td>
                                        <td><x-status-badge :status="$provider->status" domain="integration_provider" /></td>
                                        <td>
                                            @if($provider->is_active)
                                                <i class="ti ti-circle-check-filled text-success" title="{{ __('integrations.active_provider') }}"></i>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($provider->last_test_status)
                                                <x-status-badge :status="$provider->last_test_status" domain="provider_test" size="sm" />
                                            @else
                                                <span class="text-muted small">{{ __('integrations.not_tested') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                @can('integrations.payments.test')
                                                    <x-confirm-form :action="route('admin.integrations.payments.providers.test', $provider)"
                                                        buttonClass="btn btn-sm btn-outline-secondary" icon="ti-plug-connected"
                                                        :buttonLabel="__('integrations.test')"
                                                        :confirmTitle="__('integrations.test_connection')" :confirmText="__('integrations.test_connection')" />
                                                @endcan
                                                @can('integrations.payments.providers.activate')
                                                    @if($provider->is_active)
                                                        <x-confirm-form :action="route('admin.integrations.payments.providers.deactivate', $provider)"
                                                            buttonClass="btn btn-sm btn-outline-warning" icon="ti-plug-off"
                                                            :buttonLabel="__('integrations.deactivate')"
                                                            :confirmTitle="__('integrations.deactivate_provider')" :confirmText="__('integrations.deactivate_provider')" />
                                                    @else
                                                        <x-confirm-form :action="route('admin.integrations.payments.providers.activate', $provider)"
                                                            buttonClass="btn btn-sm btn-outline-success" icon="ti-plug-connected"
                                                            :buttonLabel="__('integrations.activate')"
                                                            :confirmTitle="__('integrations.activate_provider')" :confirmText="__('integrations.activation_warning')" />
                                                    @endif
                                                @endcan
                                                @can('integrations.payments.providers.manage')
                                                    <a href="{{ route('admin.integrations.payments.providers.edit', $provider) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <p class="text-muted small mt-3 mb-0"><i class="ti ti-info-circle me-1"></i>{{ __('integrations.only_one_active') }}</p>
    </div>
</div>
@endsection
