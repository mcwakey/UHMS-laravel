@extends('layouts.error')

@section('title', 'Session expired')
@section('variant', 'warning')
@section('icon', 'ti-clock-exclamation')
@section('code', '419')
@section('heading', 'Session expired')
@section('message', 'Your session has expired for security reasons. Please refresh the page and try again, or log in again if needed.')

@section('actions')
    @include('errors.partials.actions', ['reload' => true, 'login' => true])
@endsection
