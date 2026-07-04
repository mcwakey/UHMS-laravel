@extends('layouts.app')
@section('title', __('maternity.new_pregnancy_profile'))
@section('content')
<x-page-header :title="__('maternity.new_pregnancy_profile')" icon="ti-plus" />
<form method="POST" action="{{ route('admin.maternity.pregnancies.store') }}">@csrf @include('maternity.pregnancies.partials.form')</form>
@endsection
