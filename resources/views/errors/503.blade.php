@extends('layouts.error')

@section('title', 'System temporarily unavailable')
@section('variant', 'info')
@section('icon', 'ti-tool')
@section('code', '503')
@section('heading', 'System temporarily unavailable')
@section('message', 'UHMS is temporarily unavailable due to maintenance or a temporary service issue. Please try again shortly.')

@section('actions')
    @include('errors.partials.actions', ['reload' => true, 'dashboard' => true])
@endsection
