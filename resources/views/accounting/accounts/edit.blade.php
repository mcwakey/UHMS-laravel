@extends('layouts.app')
@section('title', __('accounting.edit_account'))

@section('content')
<x-page-header :title="__('accounting.edit_account')" icon="ti-edit">
    <x-slot:actions>
        <a href="{{ route('admin.accounting.accounts.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
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
                <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>{{ __('common.save_changes') }}</button>
            </div>
        </form>
        <div class="mt-2">
            @if($account->is_active)
                @can('accounting.accounts.disable')
                    <x-confirm-form :action="route('admin.accounting.accounts.disable', $account)" method="PATCH" :button-label="__('accounting.disable_account')" button-class="btn btn-outline-danger" icon="ti-ban" :confirm-title="__('accounting.disable_account_question')" :confirm-text="__('accounting.inactive_accounts_cannot_be_used')" :confirm-button="__('accounting.disable')" />
                @endcan
            @else
                @can('accounting.accounts.edit')
                    <x-confirm-form :action="route('admin.accounting.accounts.activate', $account)" method="PATCH" :button-label="__('accounting.reactivate_account')" button-class="btn btn-outline-success" icon="ti-refresh" :confirm-title="__('accounting.reactivate_account_question')" :confirm-text="__('accounting.account_can_be_used_again')" :confirm-button="__('accounting.reactivate')" />
                @endcan
            @endif
        </div>
    </div>
</div>
@endsection
