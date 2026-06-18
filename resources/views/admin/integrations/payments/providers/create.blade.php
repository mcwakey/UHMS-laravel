@extends('layouts.app')

@section('title', __('integrations.add_provider'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('integrations.add_provider')" :description="__('payments.gateway.payment_gateway')" icon="ti-credit-card"
            :breadcrumbs="[
                ['label' => __('payments.gateway.payment_providers'), 'url' => route('admin.integrations.payments.providers.index')],
                ['label' => __('integrations.add_provider')],
            ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.integrations.payments.providers.store') }}">
                    @csrf
                    @include('admin.integrations.payments.providers._fields')

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('integrations.save') }}</button>
                        <a href="{{ route('admin.integrations.payments.providers.index') }}" class="btn btn-light">{{ __('integrations.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
