@extends('layouts.app')

@section('title', __('integrations.add_provider'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('integrations.add_provider')" :description="__('sms.sms_gateway')" icon="ti-message-2"
            :breadcrumbs="[
                ['label' => __('sms.sms_providers'), 'url' => route('admin.integrations.sms.providers.index')],
                ['label' => __('integrations.add_provider')],
            ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.integrations.sms.providers.store') }}">
                    @csrf
                    @include('admin.integrations.sms.providers._fields', ['provider' => null])

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('integrations.save') }}</button>
                        <a href="{{ route('admin.integrations.sms.providers.index') }}" class="btn btn-light">{{ __('integrations.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
