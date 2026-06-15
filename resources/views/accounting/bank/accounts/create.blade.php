@extends('layouts.app')
@section('title', __('accounting.add_bank_account'))

@section('content')
<x-page-header :title="__('accounting.add_bank_account')" icon="ti-building-bank" />
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.accounting.bank.accounts.store') }}">
            @csrf
            @include('accounting.bank.accounts._form', ['bankAccount' => null])
            <div class="mt-3 text-end">
                <a href="{{ route('admin.accounting.bank.accounts.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                <button class="btn btn-primary" type="submit"><i class="ti ti-check me-1"></i>{{ __('common.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
