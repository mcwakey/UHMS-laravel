@extends('layouts.app')

@section('title', __('sms.automatic_sms_events'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('sms.automatic_sms_events')" :description="__('sms.sms_gateway')" icon="ti-bell-cog" />

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent"><strong>{{ __('sms.automatic_sms_events') }}</strong></div>
                    <div class="card-body">
                        <p class="text-muted small">{{ __('sms.automatic_events_hint') }}</p>
                        <form method="POST" action="{{ route('admin.integrations.sms.events.update') }}">
                            @csrf
                            @method('PUT')
                            @foreach($toggles as $key => $enabled)
                                <div class="form-check form-switch mb-2">
                                    <input type="hidden" name="{{ $key }}" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="tg_{{ $key }}"
                                           name="{{ $key }}" value="1" @checked($enabled)>
                                    <label class="form-check-label" for="tg_{{ $key }}">{{ __('sms.' . $key) }}</label>
                                </div>
                            @endforeach
                            <button type="submit" class="btn btn-primary mt-2"><i class="ti ti-check me-1"></i>{{ __('integrations.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent"><strong>{{ __('sms.sms_notification_events') }}</strong></div>
                    <div class="card-body p-0">
                        @if($events->isEmpty())
                            <x-empty-state icon="ti-bell" :message="__('sms.no_events')" />
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('sms.event_type') }}</th>
                                            <th>{{ __('sms.recipient') }}</th>
                                            <th>{{ __('sms.status') }}</th>
                                            <th>{{ __('sms.triggered_at') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($events as $event)
                                            <tr>
                                                <td><span class="badge badge-soft-secondary">{{ $event->event_type }}</span></td>
                                                <td class="small"><x-patient-protected-field field="phone" :value="$event->recipient_phone" /></td>
                                                <td>
                                                    <span class="badge bg-{{ $event->status === 'sent' ? 'success' : ($event->status === 'skipped' ? 'secondary' : ($event->status === 'failed' ? 'danger' : 'warning')) }}">{{ $event->status }}</span>
                                                    @if($event->error_message)<div class="text-muted small">{{ $event->error_message }}</div>@endif
                                                </td>
                                                <td class="small text-muted">{{ $event->triggered_at?->format('Y-m-d H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-transparent">{{ $events->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
