@extends('layouts.app')

@section('title', __('sms.sms_queue'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('sms.sms_queue')" :description="__('sms.sms_gateway')" icon="ti-mail-fast">
            <x-slot:actions>
                @can('integrations.sms.status.reconcile')
                    <form method="POST" action="{{ route('admin.integrations.sms.status.reconcile') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary"><i class="ti ti-refresh me-1"></i>{{ __('sms.reconcile_now') }}</button>
                    </form>
                @endcan
            </x-slot:actions>
        </x-page-header>

        <p class="text-muted small"><i class="ti ti-server me-1"></i>{{ __('sms.queue_driver') }}: <code>{{ $queueDriver }}</code></p>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($messages->isEmpty())
                    <x-empty-state icon="ti-mail-fast" :message="__('sms.no_messages')" />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('sms.message_body') }}</th>
                                    <th>{{ __('integrations.provider') }}</th>
                                    <th>{{ __('sms.recipients') }}</th>
                                    <th>{{ __('sms.failed_recipients') }}</th>
                                    <th>{{ __('sms.retry_count') }}</th>
                                    <th>{{ __('sms.status') }}</th>
                                    <th class="text-end">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($messages as $message)
                                    <tr>
                                        <td class="text-truncate" style="max-width:260px;">{{ $message->message_body }}</td>
                                        <td>{{ $message->provider?->name ?? '—' }}</td>
                                        <td>{{ $message->recipients_count }}</td>
                                        <td>{{ $message->failed_count }}</td>
                                        <td>{{ $message->retry_count }}/{{ $message->max_retries }}</td>
                                        <td><x-status-badge :status="$message->status" domain="sms_message" /></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="{{ route('admin.integrations.sms.messages.show', $message) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-eye"></i></a>
                                                @can('integrations.sms.queue.retry')
                                                    @if(in_array($message->status, ['failed','partially_sent'], true))
                                                        <x-confirm-form :action="route('admin.integrations.sms.queue.retry', $message)"
                                                            buttonClass="btn btn-sm btn-outline-primary" icon="ti-refresh"
                                                            :buttonLabel="__('sms.retry_sms')" :confirmTitle="__('sms.retry_sms')" :confirmText="__('sms.retry_sms')" />
                                                    @endif
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
            <div class="card-footer bg-transparent">{{ $messages->links() }}</div>
        </div>
    </div>
</div>
@endsection
