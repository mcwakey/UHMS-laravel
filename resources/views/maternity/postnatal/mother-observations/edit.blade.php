@extends('layouts.app')
@section('title', __('maternity.edit_mother_observation'))
@section('content')
<x-page-header :title="__('maternity.edit_mother_observation')" :subtitle="$case->mother?->full_name" icon="ti-stethoscope">
    <x-slot:actions><a href="{{ route('admin.maternity.postnatal.mother-observations.show', $observation) }}" class="btn btn-outline-secondary btn-md fs-13">{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>
<form method="POST" action="{{ route('admin.maternity.postnatal.mother-observations.update', $observation) }}">
    @include('maternity.postnatal.mother-observations.partials.form')
</form>
@endsection
