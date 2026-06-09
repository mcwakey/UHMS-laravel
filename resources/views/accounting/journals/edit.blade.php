@extends('layouts.app')
@section('title', 'Edit Journal Entry')

@section('content')
<x-page-header title="Edit Journal Entry" icon="ti-edit">
    <x-slot:actions>
        <a href="{{ route('admin.accounting.journals.show', $journal) }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>Back</a>
    </x-slot:actions>
</x-page-header>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('admin.accounting.journals.update', $journal) }}">
    @csrf
    @method('PUT')
    @include('accounting.journals._form')
    <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save Changes</button>
</form>
@endsection
