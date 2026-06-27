@extends('layouts.app')
@section('title', $dashboard['title'] ?? __('dashboards.department.department_dashboard'))

@section('content')
@php
    // Bespoke, fully-customised pharmacy dashboard (per-type showcase).
    $dashboardPersonalization = ['key' => 'pharmacy'];
@endphp
@include('admin.dashboards.department.partials._chrome')
@include('admin.dashboards.department.partials.layouts.dispensing_showcase')
@endsection
