@extends('layouts.app')

@section('title', __('sms.sms_message'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('sms.sms_message')" :description="$message->message_uuid" icon="ti-message"
            :breadcrumbs="[
                ['label' => __('sms.sms_messages'), 'url' => route('admin.integrations.sms.messages.index')],
                ['label' => $message->message_uuid],
            ]">
            <x-slot:actions>
                <x-status-badge :status="$message->status" domain="sms_message" />
                @can('integrations.sms.send')
                    @if(in_array($message->status, ['failed','partially_sent'], true))
                        <x-confirm-form :action="route('admin.integrations.sms.messages.resend', $message)"
                            buttonClass="btn btn-outline-primary btn-sm" icon="ti-refresh"
                            :buttonLabel="__('sms.resend')" :confirmTitle="__('sms.resend')" :confirmText="__('sms.resend')" />
                    @endif
                @endcan
            </x-slot:actions>
        </x-page-header>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-5">{{ __('sms.type') }}</dt><dd class="col-7">{{ $message->message_type }}</dd>
                            <dt class="col-5">{{ __('integrations.provider') }}</dt><dd class="col-7">{{ $message->provider?->name ?? '—' }}</dd>
                            <dt class="col-5">{{ __('sms.sender_id') }}</dt><dd class="col-7">{{ $message->sender_id ?? '—' }}</dd>
                            <dt class="col-5">{{ __('sms.batch_reference') }}</dt><dd class="col-7">{{ $message->provider_batch_reference ?? '—' }}</dd>
                            <dt class="col-5">{{ __('sms.created_at') }}</dt><dd class="col-7">{{ $message->created_at?->format('Y-m-d H:i') }}</dd>
                        </dl>
                        <hr>
                        <p class="mb-0">{{ $message->message_body }}</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent"><strong>{{ __('sms.recipients') }}</strong></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('sms.phone') }}</th>
                                        <th>{{ __('sms.normalized_phone') }}</th>
                                        <th>{{ __('sms.status') }}</th>
                                        <th>{{ __('sms.provider_message_id') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($message->recipients as $recipient)
                                        <tr>
                                            <td>{{ $recipient->phone_number }}</td>
                                            <td><code>{{ $recipient->normalized_phone_number }}</code></td>
                                            <td>
                                                <x-status-badge :status="$recipient->status" domain="sms_recipient" size="sm" />
                                                @if($recipient->error_message)<div class="text-danger small">{{ $recipient->error_message }}</div>@endif
                                            </td>
                                            <td class="small text-muted">{{ $recipient->provider_message_id ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
