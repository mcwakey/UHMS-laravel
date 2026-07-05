@extends('layouts.app')
@section('title', __('maternity.record_mother_observation'))
@section('content')
<x-page-header :title="__('maternity.record_mother_observation')" :subtitle="$case->mother?->full_name" icon="ti-stethoscope">
    <x-slot:actions><a href="{{ route('admin.maternity.postnatal.show', $case) }}" class="btn btn-outline-secondary btn-md fs-13">{{ __('common.back') }}</a></x-slot:actions>
</x-page-header>
<form method="POST" action="{{ route('admin.maternity.postnatal.mother-observations.store', $case) }}">
    @include('maternity.postnatal.mother-observations.partials.form')
</form>
@endsection
