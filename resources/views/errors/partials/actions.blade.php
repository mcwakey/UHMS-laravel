{{-- Reusable error-page action buttons. Pass any of: back, reload, dashboard, login (booleans). --}}
@php
    try {
        $dashboardUrl = auth()->check()
            ? app(\App\Services\WorkspaceRouteResolver::class)->dashboard()
            : (\Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/'));
    } catch (\Throwable) {
        $dashboardUrl = auth()->check() && \Illuminate\Support\Facades\Route::has('admin.my-dashboard')
            ? route('admin.my-dashboard')
            : (\Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/'));
    }
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
@endphp
@if($back ?? false)
    <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>Go back</a>
@endif
@if($reload ?? false)
    <a href="{{ url()->current() }}" onclick="event.preventDefault(); window.location.reload();" class="btn btn-outline-secondary"><i class="ti ti-refresh me-1"></i>Reload page</a>
@endif
@if($dashboard ?? false)
    <a href="{{ $dashboardUrl }}" class="btn btn-primary"><i class="ti ti-layout-dashboard me-1"></i>Go to dashboard</a>
@endif
@if($login ?? false)
    <a href="{{ $loginUrl }}" class="btn btn-primary"><i class="ti ti-login me-1"></i>Go to login</a>
@endif
