{{-- Statistics page navigation pills — only pages the user may view ($catalogue is pre-filtered). --}}
<div class="d-flex flex-wrap gap-1 mb-3">
    <a href="{{ route('admin.statistics.dashboard', $filters ?? []) }}" class="btn btn-sm {{ ($key ?? '') === '' || request()->routeIs('admin.statistics.dashboard') ? 'btn-primary' : 'btn-outline-secondary' }}">
        <i class="ti ti-layout-dashboard me-1"></i>Dashboard
    </a>
    @foreach($catalogue as $catKey => $meta)
        <a href="{{ route('admin.statistics.'.$catKey, $filters ?? []) }}" class="btn btn-sm {{ ($key ?? '') === $catKey ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="ti {{ $meta['icon'] ?? 'ti-chart-bar' }} me-1"></i>{{ $meta['title'] }}
        </a>
    @endforeach
</div>
