<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ URL::asset('build/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/tabler-icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/style.css') }}">
</head>
<body>
    <div class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
        <div class="text-center">
            <div class="mb-4">
                <i class="ti ti-file-unknown" style="font-size: 80px; color: #6c757d;"></i>
            </div>
            <h1 class="display-1 fw-bold text-primary">404</h1>
            <h4 class="fw-bold mb-2">Page Not Found</h4>
            <p class="text-muted mb-4">The page you are looking for doesn't exist or has been moved.</p>
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="ti ti-arrow-left me-1"></i>Back to Home
            </a>
        </div>
    </div>
</body>
</html>
