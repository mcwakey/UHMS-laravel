<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- PWA --}}
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="UHMS">
    <link rel="apple-touch-icon" href="{{ asset('build/img/pwa/icon-192.png') }}">
    <link rel="manifest" href="/manifest.webmanifest">

    <title inertia>{{ config('app.name', 'UHMS') }}</title>

    @vite(['resources/css/style.css', 'resources/js/inertia.js'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
