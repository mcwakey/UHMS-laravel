<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', __('payments.gateway.public_payment')) - {{ config('app.name') }}</title>

    @include('layouts.partials.frame-breaker')

    <link rel="shortcut icon" href="{{ URL::asset('build/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/tabler-icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/style.css') }}">
</head>
<body class="bg-light">
    <div class="container py-4" style="max-width: 460px;">
        <div class="text-center mb-3">
            <h5 class="fw-bold mb-0">{{ config('app.name') }}</h5>
            <div class="text-muted small">{{ __('payments.gateway.public_payment') }}</div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-circle-check me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
        @include('layouts.partials.flash-dedupe')

        <p class="text-center text-muted mt-4" style="font-size:.75rem;">{{ config('app.name') }}</p>
    </div>

    <script src="{{ URL::asset('build/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
