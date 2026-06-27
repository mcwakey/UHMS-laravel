@extends('layouts.app')
@section('title', $dashboard['title'] ?? __('dashboards.department.department_dashboard'))

@section('content')
@php
    // Bespoke, fully-customised consultation dashboard (the per-type showcase).
    $dashboardPersonalization = ['key' => 'consultation'];
@endphp
@include('admin.dashboards.department.partials._chrome')
@include('admin.dashboards.department.partials.layouts.clinical_showcase')
@endsection
