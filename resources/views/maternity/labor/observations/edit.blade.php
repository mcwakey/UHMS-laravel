@extends('layouts.app')
@section('title', __('maternity.labor_observation'))
@section('content')
<x-page-header :title="__('maternity.labor_observation')" :subtitle="$episode->patient?->full_name" icon="ti-chart-line">
    <x-slot:actions><a href="{{ route('admin.maternity.labor.show', $episode) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>
<form method="POST" action="{{ route('admin.maternity.labor.observations.update', $observation) }}">@csrf @method('PATCH') @include('maternity.labor.observations.partials.form')</form>
@endsection
