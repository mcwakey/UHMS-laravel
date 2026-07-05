@extends('layouts.app')
@section('title', __('maternity.record_anc_visit'))
@section('content')
<x-page-header :title="__('maternity.record_anc_visit')" :subtitle="$profile->patient?->full_name" icon="ti-stethoscope">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.pregnancies.show', $profile) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
    </x-slot:actions>
</x-page-header>
<form method="POST" action="{{ route('admin.maternity.pregnancies.antenatal.store', $profile) }}">
    @csrf
    @include('maternity.antenatal.partials.form')
</form>
@endsection
