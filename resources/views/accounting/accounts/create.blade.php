@extends('layouts.app')
@section('title', 'Create Account')

@section('content')
<x-page-header title="Create Account" icon="ti-plus">
    <x-slot:actions>
        <a href="{{ route('admin.accounting.accounts.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>Back</a>
    </x-slot:actions>
</x-page-header>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.accounting.accounts.store') }}">
            @csrf
            @include('accounting.accounts._form')
            <div class="mt-3">
                <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save Account</button>
            </div>
        </form>
    </div>
</div>
@endsection
