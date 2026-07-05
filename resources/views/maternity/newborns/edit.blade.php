@extends('layouts.app')
@section('title', __('maternity.newborn_record'))
@section('content')
<x-page-header :title="__('maternity.newborn_record')" :subtitle="$record->mother?->full_name" icon="ti-baby-bottle">
    <x-slot:actions><a href="{{ route('admin.maternity.newborns.show', $record) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>
<form method="POST" action="{{ route('admin.maternity.newborns.update', $record) }}">@csrf @method('PATCH') @include('maternity.newborns.partials.form')</form>
@endsection
