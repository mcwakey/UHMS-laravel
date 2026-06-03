@extends('layouts.error')

@section('title', 'Page not found')
@section('variant', 'secondary')
@section('icon', 'ti-file-search')
@section('code', '404')
@section('heading', 'Page not found')
@section('message', 'The page or record you are looking for could not be found. It may have been moved, deleted, or you may not have access to it.')

@section('actions')
    @include('errors.partials.actions', ['back' => true, 'dashboard' => true])
@endsection
