@extends('layouts.app')

@section('title', __('integrations.go_live_checklist'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('integrations.go_live_checklist')" :description="__('integrations.integrations')" icon="ti-rocket" />

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($providers->isEmpty())
                    <x-empty-state icon="ti-rocket" :message="__('integrations.no_active_provider')" />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('integrations.provider') }}</th>
                                    <th>{{ __('integrations.environment') }}</th>
                                    <th>{{ __('integrations.go_live_ready') }}</th>
                                    <th>{{ __('integrations.status') }}</th>
                                    <th class="text-end">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($providers as $provider)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $provider->name }}</div>
                                            <div class="text-muted small"><code>{{ $provider->code }}</code> · {{ $provider->module_type }}</div>
                                        </td>
                                        <td><x-status-badge :status="$provider->environment" domain="default" soft /></td>
                                        <td>
                                            @if(($checklists[$provider->id] ?? false))
                                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>{{ __('integrations.go_live_ready') }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ __('integrations.not_live_ready') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $statuses[$provider->id] ?? '—' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.integrations.golive.show', $provider) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-list-check me-1"></i>{{ __('integrations.go_live_checklist') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
