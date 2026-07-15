{{--
    Lightweight, self-contained layout for friendly error/maintenance pages.

    It intentionally does NOT use the main app layout (which loads the sidebar,
    notifications and permission data) because that data may itself be the cause
    of a 500 — an error page must always render. Bootstrap 5 + Tabler Icons only.

    Child pages provide: @section('title'), variant, icon, code (optional),
    heading, message, and @section('actions'). A @section('support') is optional.
--}}
@php
    $dashboardUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard')
        ? route('admin.dashboard')
        : (\Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/'));
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
    $variant = trim($__env->yieldContent('variant', 'danger'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error') · {{ config('app.name', 'UHMS') }}</title>
    <link rel="shortcut icon" href="{{ URL::asset('build/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/tabler-icons/tabler-icons.min.css') }}?v={{ filemtime(public_path('build/plugins/tabler-icons/tabler-icons.min.css')) }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/style.css') }}">
</head>
<body class="bg-light">
    <main class="d-flex align-items-center justify-content-center min-vh-100 p-3">
        <div class="card border-0 shadow-sm w-100" style="max-width: {{ $__env->hasSection('debug') ? '900px' : '540px' }};">
            <div class="card-body text-center p-4 p-md-5">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $variant }}-subtle"
                          style="width:84px;height:84px;">
                        <i class="ti @yield('icon', 'ti-alert-triangle') text-{{ $variant }}" style="font-size:42px;" aria-hidden="true"></i>
                    </span>
                </div>
                @hasSection('code')
                    <div class="display-5 fw-bold text-{{ $variant }} mb-1">@yield('code')</div>
                @endif
                <h1 class="h4 fw-bold mb-2">@yield('heading', 'Something went wrong')</h1>
                <p class="text-muted mb-4">@yield('message', 'The system could not complete your request.')</p>

                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    @yield('actions')
                </div>

                @hasSection('support')
                    <div class="mt-4 pt-3 border-top small text-muted">@yield('support')</div>
                @endif

                @hasSection('debug')
                    <div class="mt-4 pt-3 border-top small text-start">
                        <div class="fw-semibold text-warning mb-1"><i class="ti ti-bug me-1"></i>Debug info (APP_DEBUG only — never shown in production)</div>
                        @yield('debug')
                    </div>
                @endif
            </div>
        </div>
    </main>
</body>
</html>
