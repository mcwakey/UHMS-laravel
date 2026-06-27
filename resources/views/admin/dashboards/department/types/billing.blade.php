@extends('layouts.app')
@section('title', $dashboard['title'] ?? __('dashboards.department.department_dashboard'))

@section('content')
@php $dashboardPersonalization = ['key' => 'billing']; @endphp
@include('admin.dashboards.department.partials._chrome')
@include('admin.dashboards.department.partials.layouts.finance_showcase')
@endsection
