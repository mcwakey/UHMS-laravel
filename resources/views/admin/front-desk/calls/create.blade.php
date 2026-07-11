@extends('layouts.app')
@section('title', __('front_desk.calls.new'))

@section('content')
<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('front_desk.calls.new') }}</h4>
    </div>
    <a href="{{ route('admin.front-desk.calls.index') }}" class="btn btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i>{{ __('front_desk.actions.back') }}
    </a>
</div>

@include('admin.front-desk.calls._form', ['log' => null])
@endsection
