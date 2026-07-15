@extends('layouts.error')

@section('title', 'Access denied')
@section('variant', 'danger')
@section('icon', 'ti-lock')
@section('code', '403')
@section('heading', 'Access denied')
@section('message', 'You do not have permission to access this page or perform this action.')

@section('actions')
    @include('errors.partials.actions', ['back' => true, 'dashboard' => true])
@endsection

@php
    // Surface a custom abort reason ONLY when it is short and free of technical
    // markers (no SQL, paths, class names) — otherwise keep it generic.
    $reason = isset($exception) ? trim((string) $exception->getMessage()) : '';
    $safeReason = ($reason !== ''
        && mb_strlen($reason) <= 160
        && ! preg_match('/SQLSTATE|Exception|::|\\\\|\\/var\\/|\\.php|vendor|column|table/i', $reason))
        ? $reason : null;
@endphp
@if($safeReason)
    @section('support')
        {{ $safeReason }}<br>
        If you believe you should have access, please contact your system administrator.
    @endsection
@else
    @section('support', 'If you believe you should have access, please contact your system administrator.')
@endif

@if(config('app.debug') && isset($authDebug))
    @section('debug')
        <ul class="mb-2 ps-3 fs-13">
            <li><strong>Route:</strong> <code>{{ $authDebug['route_name'] ?? '—' }}</code> &rarr; <code>{{ $authDebug['controller_action'] ?? '—' }}</code></li>
            <li><strong>Exception:</strong> {{ $authDebug['exception_class'] }} &mdash; {{ $authDebug['message'] }}</li>
            @if($authDebug['thrown_at'])
                <li>
                    <strong>Thrown at:</strong> <code>{{ $authDebug['thrown_at'] }}</code>
                    @if($authDebug['thrown_in'])
                        in <code>{{ $authDebug['thrown_in'] }}</code>
                    @endif
                </li>
            @endif
            @if($authDebug['other_middleware']->isNotEmpty())
                <li>
                    <strong>Role / module / department middleware on this route:</strong>
                    @foreach($authDebug['other_middleware'] as $m)
                        <code>{{ $m }}</code>@if(!$loop->last), @endif
                    @endforeach
                    <span class="text-muted">(not grantable below — these gate on role membership or module state, not a permission)</span>
                </li>
            @endif
        </ul>

        @if($authDebug['permission_requirements']->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0 fs-13 bg-white">
                    <thead class="table-light">
                        <tr>
                            <th>Permission</th>
                            <th>Description</th>
                            <th>Risk</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($authDebug['permission_requirements'] as $row)
                            <tr>
                                <td><code>{{ $row['name'] }}</code></td>
                                <td class="text-muted">{{ $row['description'] }}</td>
                                <td><span class="badge bg-{{ strtolower($row['risk']) === 'critical' ? 'danger' : 'secondary' }}">{{ $row['risk'] }}</span></td>
                                <td>
                                    @if($row['granted'])
                                        <span class="badge bg-success">Granted</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Missing</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if(! $row['granted'])
                                        <form method="POST" action="{{ route('debug.grant-permission') }}" class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                            @csrf
                                            <input type="hidden" name="permission" value="{{ $row['name'] }}">
                                            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                            @if($authDebug['current_user_roles']->isNotEmpty())
                                                <select name="role_id" class="form-select form-select-sm w-auto">
                                                    @foreach($authDebug['current_user_roles'] as $r)
                                                        <option value="{{ $r['id'] }}">{{ $r['name'] }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" name="target" value="role" class="btn btn-sm btn-outline-primary">Add to role</button>
                                            @endif
                                            <button type="submit" name="target" value="user" class="btn btn-sm btn-outline-secondary">Add to me</button>
                                        </form>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-muted fs-12 mt-2 mb-0">Dev-only: grants are logged to the normal security audit trail and only ever affect your own account/roles.</p>
        @endif
    @endsection
@endif
