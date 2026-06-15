@extends('layouts.app')
@section('title', __('accounting.new_mapping'))
@section('content')
<x-page-header :title="__('accounting.new_mapping')" icon="ti-arrows-random" />
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('admin.accounting.mappings.store') }}">
        @include('accounting.mappings._form')
    </form>
</div></div>
@endsection
