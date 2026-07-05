@extends('layouts.app')
@section('title', __('maternity.create_newborn_record'))
@section('content')
<x-page-header :title="__('maternity.create_newborn_record')" :subtitle="$delivery->patient?->full_name" icon="ti-baby-bottle">
    <x-slot:actions><a href="{{ route('admin.maternity.deliveries.show', $delivery) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>
<form method="POST" action="{{ route('admin.maternity.deliveries.newborns.store', $delivery) }}">@csrf @include('maternity.newborns.partials.form')</form>
@endsection
