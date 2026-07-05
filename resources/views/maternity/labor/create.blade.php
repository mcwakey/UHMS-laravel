@extends('layouts.app')
@section('title', __('maternity.start_labor_episode'))
@section('content')
<x-page-header :title="__('maternity.start_labor_episode')" :subtitle="$profile->patient?->full_name" icon="ti-baby-carriage">
    <x-slot:actions><a href="{{ route('admin.maternity.pregnancies.show', $profile) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>
<form method="POST" action="{{ route('admin.maternity.pregnancies.labor.store', $profile) }}">
    @csrf
    @include('maternity.labor.partials.episode-form')
</form>
@endsection
