@extends('layouts.app')
@section('title', $dashboard['title'] ?? __('dashboards.department.department_dashboard'))

@section('content')
@include('admin.dashboards.department.partials.dashboard-shell', ['dashboardPersonality' => 'reception'])
@endsection
