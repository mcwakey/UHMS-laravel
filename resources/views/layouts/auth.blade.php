<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') - {{ config('app.name') }}</title>

    @include('layouts.partials.frame-breaker')

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/img/favicon.png') }}">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/fontawesome/css/all.min.css') }}">

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/tabler-icons/tabler-icons.min.css') }}?v={{ filemtime(public_path('build/plugins/tabler-icons/tabler-icons.min.css')) }}">

    <!-- Template Style -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/style.css') }}">

    <!-- UHMS Design Rules -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/uhms-design-system.css') }}?v={{ filemtime(public_path('build/css/uhms-design-system.css')) }}">

    @stack('styles')
</head>
<body>
    <div class="main-wrapper auth-bg auth-bg-custom position-relative overflow-hidden">
        @yield('content')
    </div>

    <!-- jQuery -->
    <script src="{{ URL::asset('build/js/jquery-3.7.1.min.js') }}"></script>

    <!-- Bootstrap JS -->
    <script src="{{ URL::asset('build/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Template Script -->
    <script src="{{ URL::asset('build/js/script.js') }}"></script>

    @stack('scripts')
</body>
</html>
