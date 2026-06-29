@extends('layouts.app')
@section('title', __('journey.notification.settings'))

@section('content')
<div class="mb-3">
    <h4 class="fw-bold mb-0"><i class="ti ti-bell-cog me-1"></i>{{ __('journey.notification.settings') }}</h4>
    <small class="text-muted">{{ __('journey.notification.settings_subtitle') }}</small>
</div>

@if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

<form method="POST" action="{{ route('admin.settings.journey-notifications.update') }}" class="card shadow-sm">
    @csrf
    @method('PUT')
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('journey.notification.event') }}</th>
                    <th class="text-center">{{ __('journey.notification.in_app') }}</th>
                    <th class="text-center">{{ __('journey.notification.email') }}@unless($emailEnabled)<span class="badge bg-secondary ms-1">{{ __('journey.notification.off') }}</span>@endunless</th>
                    <th class="text-center">{{ __('journey.notification.sms') }}@unless($smsEnabled)<span class="badge bg-secondary ms-1">{{ __('journey.notification.off') }}</span>@endunless</th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                    @php $pref = $preferences[$event->value] ?? ['in_app' => true, 'email' => false, 'sms' => false]; @endphp
                    <tr>
                        <td><i class="ti {{ $event->icon() }} me-1"></i>{{ $event->translatedLabel() }}</td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input" name="preferences[{{ $event->value }}][in_app]" value="1" @checked($pref['in_app'])>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input" name="preferences[{{ $event->value }}][email]" value="1" @checked($pref['email']) @disabled(!$emailEnabled)>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input" name="preferences[{{ $event->value }}][sms]" value="1" @checked($pref['sms']) @disabled(!$smsEnabled)>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer text-end">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('journey.worklist.apply') }}</button>
    </div>
</form>
@endsection
