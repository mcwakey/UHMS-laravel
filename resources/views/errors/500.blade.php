@extends('layouts.error')

@section('title', 'Something went wrong')
@section('variant', 'danger')
@section('icon', 'ti-alert-triangle')
@section('code', '500')
@section('heading', 'Something went wrong')
@section('message', 'The system could not complete your request. Please try again. If the problem continues, contact the system administrator.')

@section('actions')
    @include('errors.partials.actions', ['back' => true, 'reload' => true, 'dashboard' => true])
@endsection

@section('support', 'Technical details of this error have been logged for the support team.')
