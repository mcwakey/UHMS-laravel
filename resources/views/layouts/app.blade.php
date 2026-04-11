<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'UHMS') - {{ config('app.name') }}</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/img/favicon.png') }}">

    <!-- Theme Config -->
    <script src="{{ URL::asset('build/js/theme-script.js') }}"></script>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/fontawesome/css/all.min.css') }}">

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/tabler-icons/tabler-icons.min.css') }}">

    <!-- Simplebar CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/simplebar/simplebar.min.css') }}">

    <!-- Daterangepicker CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/daterangepicker/daterangepicker.css') }}">

    <!-- Datetimepicker CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap-datetimepicker.min.css') }}">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/dataTables.bootstrap5.min.css') }}">

    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/select2/css/select2.min.css') }}">

    <!-- Template Style -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/style.css') }}">

    @stack('styles')
</head>
<body>
    <div class="main-wrapper">

        @include('layouts.partials.header')
        @include('layouts.partials.sidebar')

        <!-- Page Content -->
        <div class="page-wrapper">
            <div class="content">

                {{-- Flash Messages --}}
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ti ti-circle-check me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ti ti-alert-circle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @yield('content')
            </div>
        </div>
        <!-- /Page Content -->

        <!-- Footer -->
        <div class="footer text-center bg-white p-2 border-top">
            <p class="text-dark mb-0">
                <script>document.write(new Date().getFullYear())</script> &copy;
                <a href="javascript:void(0);" class="link-primary">UHMS</a> - Ultimate Hospital Management System
            </p>
        </div>

    </div>

    <!-- jQuery -->
    <script src="{{ URL::asset('build/js/jquery-3.7.1.min.js') }}"></script>

    <!-- Bootstrap JS -->
    <script src="{{ URL::asset('build/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Simplebar JS -->
    <script src="{{ URL::asset('build/plugins/simplebar/simplebar.min.js') }}"></script>

    <!-- Daterangepicker JS -->
    <script src="{{ URL::asset('build/js/moment.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/daterangepicker/daterangepicker.js') }}"></script>

    <!-- Datetimepicker JS -->
    <script src="{{ URL::asset('build/js/bootstrap-datetimepicker.min.js') }}"></script>

    <!-- DataTables JS -->
    <script src="{{ URL::asset('build/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/dataTables.bootstrap5.min.js') }}"></script>

    <!-- Select2 JS -->
    <script src="{{ URL::asset('build/plugins/select2/js/select2.min.js') }}"></script>

    <!-- SweetAlert2 -->
    <script src="{{ URL::asset('build/plugins/sweetalert2/sweetalerts2.min.js') }}"></script>

    <!-- Template Script -->
    <script src="{{ URL::asset('build/js/script.js') }}"></script>

    @stack('scripts')
</body>
</html>
