@extends('layouts.app')
@section('title', __('accounting.edit_mapping'))
@section('content')
<x-page-header :title="__('accounting.edit_mapping')" icon="ti-arrows-random" />
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('admin.accounting.mappings.update', $mapping) }}">
        @include('accounting.mappings._form')
    </form>
    @if($mapping->is_active)
        <hr>
        <form method="POST" action="{{ route('admin.accounting.mappings.disable', $mapping) }}">
            @csrf @method('PATCH')
            <button class="btn btn-outline-danger" type="submit">{{ __('accounting.disable_mapping') }}</button>
        </form>
    @endif
</div></div>
@endsection
