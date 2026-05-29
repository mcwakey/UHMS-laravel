@extends('layouts.app')
@section('title', 'Notification Preferences')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="fw-bold mb-0">Notification Preferences</h4>
    <a href="{{ route('admin.profile') }}" class="btn btn-sm btn-outline-secondary">Back to profile</a>
</div>

<form method="POST" action="{{ route('admin.notification-preferences.update') }}">
    @csrf @method('PUT')
    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Module</th>
                        @foreach($channels as $ch)
                            <th class="text-center">{{ ucfirst($ch) }}</th>
                        @endforeach
                        <th class="text-center">Digest</th>
                        <th>Quiet from</th>
                        <th>Quiet to</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($modules as $i => $mod)
                        @php
                            $p = $preferences->get($mod->value);
                            $selected = $p?->channels ?? ['database'];
                        @endphp
                        <tr>
                            <td>{{ $mod->label() }}
                                <input type="hidden" name="preferences[{{ $i }}][module]" value="{{ $mod->value }}">
                            </td>
                            @foreach($channels as $ch)
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input"
                                           name="preferences[{{ $i }}][channels][]"
                                           value="{{ $ch }}"
                                           {{ in_array($ch, $selected) ? 'checked' : '' }}>
                                </td>
                            @endforeach
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input"
                                       name="preferences[{{ $i }}][digest_enabled]"
                                       value="1" {{ $p?->digest_enabled ? 'checked' : '' }}>
                            </td>
                            <td>
                                <input type="time" class="form-control form-control-sm"
                                       name="preferences[{{ $i }}][quiet_hours_start]"
                                       value="{{ $p?->quiet_hours_start }}">
                            </td>
                            <td>
                                <input type="time" class="form-control form-control-sm"
                                       name="preferences[{{ $i }}][quiet_hours_end]"
                                       value="{{ $p?->quiet_hours_end }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary btn-sm">Save preferences</button>
        </div>
    </div>
</form>
@endsection
