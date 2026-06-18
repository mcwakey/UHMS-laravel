@extends('layouts.app')

@section('title', __('sms.sms_messages'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('sms.sms_messages')" :description="__('sms.sms_gateway')" icon="ti-messages">
            <x-slot:actions>
                @can('integrations.sms.send')
                    <a href="{{ route('admin.integrations.sms.messages.create') }}" class="btn btn-primary">
                        <i class="ti ti-send me-1"></i>{{ __('sms.manual_sms') }}
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($messages->isEmpty())
                    <x-empty-state icon="ti-messages" :message="__('sms.no_messages')" />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('sms.type') }}</th>
                                    <th>{{ __('sms.message_body') }}</th>
                                    <th>{{ __('sms.recipients') }}</th>
                                    <th>{{ __('integrations.provider') }}</th>
                                    <th>{{ __('sms.status') }}</th>
                                    <th>{{ __('sms.created_at') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($messages as $message)
                                    <tr>
                                        <td><span class="badge badge-soft-secondary">{{ $message->message_type }}</span></td>
                                        <td class="text-truncate" style="max-width:280px;">{{ $message->message_body }}</td>
                                        <td>{{ $message->recipients_count }}</td>
                                        <td>{{ $message->provider?->name ?? '—' }}</td>
                                        <td><x-status-badge :status="$message->status" domain="sms_message" /></td>
                                        <td class="text-muted small">{{ $message->created_at?->format('Y-m-d H:i') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.integrations.sms.messages.show', $message) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="ti ti-eye"></i>
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

        <div class="mt-3">{{ $messages->links() }}</div>
    </div>
</div>
@endsection
