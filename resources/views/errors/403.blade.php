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
