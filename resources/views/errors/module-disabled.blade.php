@extends('layouts.error')

@php
    $moduleLabel = $moduleName ?? 'requested';
@endphp

@section('title', 'Module disabled')
@section('variant', 'secondary')
@section('icon', 'ti-plug-connected-x')
@section('heading', 'Module disabled')
@section('message', 'The ' . $moduleLabel . ' module is currently disabled. Please contact your system administrator if you need access.')

@section('actions')
    @include('errors.partials.actions', ['back' => true, 'dashboard' => true])
@endsection

@if(!empty($moduleDescription))
    @section('support', $moduleDescription)
@endif
