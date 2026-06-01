<div class="list-group list-group-flush">
    <div class="list-group-item bg-light fw-bold text-muted small text-uppercase">Account</div>
    <a href="{{ route('admin.profile') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
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
    <a href="{{ route('admin.settings.ward') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('admin.settings.ward') ? 'active' : '' }}">
        <i class="ti ti-bed me-2"></i>Ward & Admissions
    </a>
    @can('complaints.catalogue.view')
    <a href="{{ route('admin.complaints.catalogue.index') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('admin.complaints.catalogue.*') ? 'active' : '' }}">
        <i class="ti ti-message-report me-2"></i>Complaint Catalogue
    </a>
    @endcan

    <div class="list-group-item bg-light fw-bold text-muted small text-uppercase mt-2">System</div>
    <a href="{{ route('admin.settings.activity-log') }}"
       class="list-group-item list-group-item-action {{ request()->routeIs('admin.settings.activity-log') ? 'active' : '' }}">
        <i class="ti ti-history me-2"></i>Activity Log
    </a>
    @endcan
</div>
