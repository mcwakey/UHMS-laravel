@extends('layouts.app')
@section('title', __('maternity.record_newborn_observation'))
@section('content')
<x-page-header :title="__('maternity.record_newborn_observation')" :subtitle="$case->mother?->full_name" icon="ti-baby-bottle">
    <x-slot:actions><a href="{{ route('admin.maternity.postnatal.show', $case) }}" class="btn btn-outline-secondary btn-md fs-13">{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>
<form method="POST" action="{{ route('admin.maternity.postnatal.newborn-observations.store', [$case, $newborn]) }}">
    @include('maternity.postnatal.newborn-observations.partials.form')
</form>
@endsection
