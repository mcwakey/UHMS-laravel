<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ URL::asset('build/img/favicon.png') }}">

    {{-- PWA --}}
    <meta name="theme-color" content="#0d6efd">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="UHMS">
    <link rel="apple-touch-icon" href="{{ URL::asset('build/img/favicon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

    <title inertia>{{ config('app.name', 'UHMS') }}</title>

    @include('layouts.partials.frame-breaker')

    <script src="{{ URL::asset('build/js/theme-script.js') }}"></script>

    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/tabler-icons/tabler-icons.min.css') }}?v={{ filemtime(public_path('build/plugins/tabler-icons/tabler-icons.min.css')) }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/simplebar/simplebar.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap-datetimepicker.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/style.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/uhms-design-system.css') }}">

    <style>
        html.uhms-loading body { visibility: hidden; }
        #sidebar { transition: none !important; }
        .sidebar-menu ul { list-style: none; padding-left: 0; margin: 0; }
        .sidebar-menu .menu-title { opacity: 0.7; }
    </style>
    <script>document.documentElement.classList.add('uhms-loading');</script>

    @inertiaHead
</head>
<body class="antialiased">
    @inertia

    <script src="{{ URL::asset('build/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/moment.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ URL::asset('build/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/chartjs/chart.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/script.js') }}"></script>

    @vite(['resources/js/inertia.js'])

    {{-- One-time global Bootstrap modal cleanup for Inertia SPA navigations.
         Fires on inertia:before so backdrops and body state are cleared before
         Vue swaps the v-html DOM — preventing scroll-lock and orphaned instances.
         The BladePage component also calls cleanupBootstrapModals() directly
         (imported from resources/js/utils/modalCleanup.js) but this inline
         handler acts as an early safety net for the inertia:before phase. --}}
    <script>
        (function () {
            if (window._uhmsModalCleanup) { return; }
            window._uhmsModalCleanup = true;

            function cleanupBootstrapModals() {
                document.querySelectorAll('.modal').forEach(function (el) {
                    try {
                        if (window.bootstrap && window.bootstrap.Modal) {
                            var inst = window.bootstrap.Modal.getInstance(el);
                            if (inst) { inst.dispose(); }
                        }
                    } catch (e) {}
                    el.classList.remove('show', 'fade');
                    el.style.display = 'none';
                    el.setAttribute('aria-hidden', 'true');
                    el.removeAttribute('aria-modal');
                    el.removeAttribute('role');
                });
                document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }

            document.addEventListener('inertia:before', cleanupBootstrapModals);
        }());
    </script>

    <script>
        (function () {
            function reveal() {
                document.documentElement.classList.remove('uhms-loading');
            }

            window.addEventListener('load', reveal);
            document.addEventListener('inertia:finish', reveal);
            document.addEventListener('inertia:navigate', reveal);

            setTimeout(reveal, 1500);
        }());
    </script>

    <script src="{{ asset('register-sw.js') }}?v={{ filemtime(public_path('register-sw.js')) }}" defer></script>
</body>
</html>
