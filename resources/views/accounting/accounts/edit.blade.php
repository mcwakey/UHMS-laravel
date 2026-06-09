@extends('layouts.app')
@section('title', 'Edit Account')

@section('content')
<x-page-header title="Edit Account" icon="ti-edit">
    <x-slot:actions>
        <a href="{{ route('admin.accounting.accounts.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>Back</a>
    </x-slot:actions>
</x-page-header>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.accounting.accounts.update', $account) }}">
            @csrf
            @method('PUT')
            @include('accounting.accounts._form')
            <div class="mt-3">
                <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save Changes</button>
            </div>
        </form>
        <div class="mt-2">
            @if($account->is_active)
                @can('accounting.accounts.disable')
                    <x-confirm-form :action="route('admin.accounting.accounts.disable', $account)" method="PATCH" button-label="Disable Account" button-class="btn btn-outline-danger" icon="ti-ban" confirm-title="Disable account?" confirm-text="Inactive accounts cannot be used in new journal entries." confirm-button="Disable" />
                @endcan
            @else
                @can('accounting.accounts.edit')
                    <x-confirm-form :action="route('admin.accounting.accounts.activate', $account)" method="PATCH" button-label="Reactivate Account" button-class="btn btn-outline-success" icon="ti-refresh" confirm-title="Reactivate account?" confirm-text="This account can be used again in new journal entries." confirm-button="Reactivate" />
                @endcan
            @endif
        </div>
    </div>
</div>
@endsection
