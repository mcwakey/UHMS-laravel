{{-- Reusable chrome (header + sidebar) for true Inertia pages.
     Rendered to string via view()->render() in HandleInertiaRequests and
     injected once by resources/js/Layouts/AppLayout.vue. Keep this file
     as a thin wrapper — DO NOT add page content here. --}}
@include('layouts.partials.header')
@include('layouts.partials.sidebar')
