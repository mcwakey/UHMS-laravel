@extends('layouts.app')
@section('title', __('accounting.new_journal_entry'))

@section('content')
<x-page-header :title="__('accounting.new_journal_entry')" icon="ti-file-plus">
    <x-slot:actions>
        <a href="{{ route('admin.accounting.journals.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
    </x-slot:actions>
</x-page-header>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('admin.accounting.journals.store') }}">
    @csrf
    @include('accounting.journals._form')
    <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>{{ __('accounting.save_draft') }}</button>
</form>
@endsection
