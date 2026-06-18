@extends('layouts.app')

@section('title', __('payments.gateway.provider_health'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('payments.gateway.provider_health')" :description="__('integrations.integrations')" icon="ti-heartbeat" />

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if(empty($rows))
                    <x-empty-state icon="ti-heartbeat" :message="__('integrations.no_active_provider')" />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('integrations.provider') }}</th>
                                    <th>{{ __('integrations.status') }}</th>
                                    <th>{{ __('integrations.last_test_result') }}</th>
                                    <th>{{ __('payments.gateway.last_success') }}</th>
                                    <th>{{ __('payments.gateway.last_failure') }}</th>
                                    <th>{{ __('payments.gateway.pending_transactions') }}</th>
                                    <th>{{ __('payments.gateway.payment_failed') }}</th>
                                    <th>{{ __('payments.gateway.undelivered_sms') }}</th>
                                    <th>{{ __('payments.gateway.callback_failures') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    @php $p = $row['provider']; @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $p->name }}</div>
                                            <div class="text-muted small"><code>{{ $p->code }}</code> · {{ $p->module_type }}</div>
                                        </td>
                                        <td><x-status-badge :status="$p->status" domain="integration_provider" /></td>
                                        <td>
                                            @if($row['last_test_status'])
                                                <x-status-badge :status="$row['last_test_status']" domain="provider_test" size="sm" />
                                            @else
                                                <span class="text-muted small">{{ __('integrations.not_tested') }}</span>
                                            @endif
                                            @if($row['last_error_message'])<div class="text-danger small text-truncate" style="max-width:220px;">{{ $row['last_error_message'] }}</div>@endif
                                        </td>
                                        <td class="small text-muted">{{ optional($row['last_success_at'])->format('Y-m-d H:i') ?? '—' }}</td>
                                        <td class="small text-muted">{{ optional($row['last_failure_at'])->format('Y-m-d H:i') ?? '—' }}</td>
                                        <td>{{ $row['pending_transactions'] ?? '—' }}</td>
                                        <td>{{ $row['failed_transactions'] ?? '—' }}</td>
                                        <td>{{ $row['undelivered_sms_count'] ?? '—' }}</td>
                                        <td>{{ $row['callback_failures'] }}</td>
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
