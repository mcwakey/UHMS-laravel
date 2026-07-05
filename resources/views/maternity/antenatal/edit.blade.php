@extends('layouts.app')
@section('title', __('maternity.edit_anc_visit'))
@section('content')
<x-page-header :title="__('maternity.edit_anc_visit')" :subtitle="$profile->patient?->full_name" icon="ti-edit">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.antenatal.show', $ancVisit) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
    </x-slot:actions>
</x-page-header>
<form method="POST" action="{{ route('admin.maternity.antenatal.update', $ancVisit) }}">
    @csrf
    @method('PATCH')
    @include('maternity.antenatal.partials.form')
</form>
@endsection
