@extends('layouts.app')
@section('title', __('maternity.edit_pregnancy_profile'))
@section('content')
<x-page-header :title="__('maternity.edit_pregnancy_profile')" icon="ti-edit" />
<form method="POST" action="{{ route('admin.maternity.pregnancies.update', $profile) }}">@csrf @method('PATCH') @include('maternity.pregnancies.partials.form')</form>
@endsection
