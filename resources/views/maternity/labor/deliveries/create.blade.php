@extends('layouts.app')
@section('title', __('maternity.create_delivery_record'))
@section('content')
<x-page-header :title="__('maternity.create_delivery_record')" :subtitle="$episode->patient?->full_name" icon="ti-confetti">
    <x-slot:actions><a href="{{ route('admin.maternity.labor.show', $episode) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>
<form method="POST" action="{{ route('admin.maternity.labor.delivery.store', $episode) }}">@csrf @include('maternity.labor.deliveries.partials.form')</form>
@endsection
