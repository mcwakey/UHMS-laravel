<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error | {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ URL::asset('build/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/tabler-icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/style.css') }}">
</head>
<body>
    <div class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
        <div class="text-center">
            <div class="mb-4">
                <i class="ti ti-server-off" style="font-size: 80px; color: #dc3545;"></i>
            </div>
            <h1 class="display-1 fw-bold text-danger">500</h1>
            <h4 class="fw-bold mb-2">Server Error</h4>
            <p class="text-muted mb-4">Something went wrong on our end. Please try again later.</p>
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="ti ti-arrow-left me-1"></i>Back to Home
            </a>
        </div>
    </div>
</body>
</html>
