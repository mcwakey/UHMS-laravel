@extends('layouts.app')

@section('title', __('integrations.scheduler_status'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('integrations.scheduler_status')" :description="__('integrations.integrations')" icon="ti-clock-cog" />

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('integrations.scheduled_commands') }}</th>
                                <th>{{ __('sms.sms_status_reconciliation') }}</th>
                                <th>{{ __('integrations.last_command_run') }}</th>
                                <th>{{ __('integrations.last_test_result') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($commands as $command => $info)
                                <tr>
                                    <td><code>{{ $command }}</code></td>
                                    <td class="small text-muted">{{ $info['recommended'] }}</td>
                                    <td class="small">{{ $info['last_run'] ? \Illuminate\Support\Carbon::parse($info['last_run'])->format('Y-m-d H:i') : '—' }}</td>
                                    <td>
                                        @if($info['last_status'])
                                            <span class="badge bg-{{ $info['last_status'] === 'success' ? 'success' : 'danger' }}">{{ $info['last_status'] }}</span>
                                        @else
                                            <span class="text-muted small">{{ __('integrations.not_tested') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-semibold">{{ __('integrations.cron_entry') }}</h6>
                <p class="text-muted small mb-2">{{ __('integrations.cron_hint') }}</p>
                <pre class="bg-light border rounded p-2 mb-0"><code>{{ $cron }}</code></pre>
            </div>
        </div>
    </div>
</div>
@endsection
