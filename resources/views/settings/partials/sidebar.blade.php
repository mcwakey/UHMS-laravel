<div class="list-group list-group-flush">
    <div class="list-group-item bg-light fw-bold text-muted small text-uppercase">Account</div>
    <a href="{{ route('admin.settings.profile') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('admin.settings.profile') ? 'active' : '' }}">
        <i class="ti ti-user me-2"></i>Profile Settings
    </a>

    @can('settings.manage')
    <div class="list-group-item bg-light fw-bold text-muted small text-uppercase mt-2">General</div>
    <a href="{{ route('admin.settings.organization') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('admin.settings.organization') ? 'active' : '' }}">
        <i class="ti ti-building me-2"></i>Organization
    </a>
    <a href="{{ route('admin.settings.invoice') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('admin.settings.invoice') ? 'active' : '' }}">
        <i class="ti ti-file-invoice me-2"></i>Invoice Settings
    </a>
    <a href="{{ route('admin.settings.payment-methods') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('admin.settings.payment-methods') ? 'active' : '' }}">
        <i class="ti ti-credit-card me-2"></i>Payment Methods
    </a>

    <div class="list-group-item bg-light fw-bold text-muted small text-uppercase mt-2">System</div>
    <a href="{{ route('admin.settings.activity-log') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('admin.settings.activity-log') ? 'active' : '' }}">
        <i class="ti ti-history me-2"></i>Activity Log
    </a>
    @endcan
</div>
