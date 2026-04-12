<!-- Topbar Start -->
<header class="navbar-header">
    <div class="page-container topbar-menu">
        <div class="d-flex align-items-center gap-2">

            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="logo">
                <span class="logo-light">
                    <span class="logo-lg"><img src="{{ URL::asset('build/img/logo.svg') }}" alt="UHMS"></span>
                    <span class="logo-sm"><img src="{{ URL::asset('build/img/logo-small.svg') }}" alt="UHMS"></span>
                </span>
                <span class="logo-dark">
                    <span class="logo-lg"><img src="{{ URL::asset('build/img/logo-white.svg') }}" alt="UHMS"></span>
                </span>
            </a>

            <!-- Sidebar Mobile Button -->
            <a id="mobile_btn" class="mobile-btn" href="#sidebar">
                <i class="ti ti-menu-deep fs-24"></i>
            </a>

            <button class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn2">
                <i class="ti ti-arrow-right"></i>
            </button>

            <!-- Search -->
            <div class="me-auto d-flex align-items-center header-search d-lg-flex d-none">
                <div class="input-icon-start position-relative me-2">
                    <span class="input-icon-addon">
                        <i class="ti ti-search"></i>
                    </span>
                    <input type="text" class="form-control shadow-sm" placeholder="Search">
                    <span class="input-icon-addon text-dark shadow fs-18 d-inline-flex p-0 header-search-icon"><i class="ti ti-command"></i></span>
                </div>
            </div>

        </div>

        <div class="d-flex align-items-center">

            <!-- Search for Mobile -->
            <div class="header-item d-flex d-lg-none me-2">
                <button class="topbar-link btn btn-icon" data-bs-toggle="modal" data-bs-target="#searchModal" type="button">
                    <i class="ti ti-search fs-16"></i>
                </button>
            </div>

            <!-- Light/Dark Mode Button -->
            <div class="header-item d-none d-sm-flex me-2">
                <button class="topbar-link btn btn-icon topbar-link" id="light-dark-mode" type="button">
                    <i class="ti ti-moon fs-16"></i>
                </button>
            </div>

            <!-- Notification Dropdown -->
            <div class="header-item">
                <div class="dropdown me-3">
                    <button class="topbar-link btn btn-icon topbar-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" data-bs-offset="0,24" type="button" aria-haspopup="false" aria-expanded="false">
                        <i class="ti ti-bell-check fs-16 animate-ring"></i>
                        <span class="notification-badge"></span>
                    </button>
                    <div class="dropdown-menu p-0 dropdown-menu-end dropdown-menu-lg" style="min-height: 300px;">
                        <div class="p-2 border-bottom">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0 fs-16 fw-semibold">Notifications</h6>
                                </div>
                            </div>
                        </div>
                        <div class="notification-body position-relative z-2 rounded-0" data-simplebar>
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-bell-off fs-24 d-block mb-2"></i>
                                No new notifications
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="dropdown profile-dropdown d-flex align-items-center justify-content-center">
                <a href="javascript:void(0);" class="topbar-link dropdown-toggle drop-arrow-none position-relative" data-bs-toggle="dropdown" data-bs-offset="0,22" aria-haspopup="false" aria-expanded="false">
                    @if(Auth::user()->avatar)
                        <img src="{{ asset('storage/' . Auth::user()->avatar) }}" width="32" class="rounded-circle d-flex" alt="avatar">
                    @else
                        <span class="avatar avatar-md rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                            {{ strtoupper(substr(Auth::user()->first_name, 0, 1) . substr(Auth::user()->last_name, 0, 1)) }}
                        </span>
                    @endif
                    <span class="online text-success"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-2">
                    <div class="d-flex align-items-center bg-light rounded-3 p-2 mb-2">
                        @if(Auth::user()->avatar)
                            <img src="{{ asset('storage/' . Auth::user()->avatar) }}" class="rounded-circle" width="42" height="42" alt="">
                        @else
                            <span class="avatar avatar-md rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                {{ strtoupper(substr(Auth::user()->first_name, 0, 1) . substr(Auth::user()->last_name, 0, 1)) }}
                            </span>
                        @endif
                        <div class="ms-2">
                            <p class="fw-medium text-dark mb-0">{{ Auth::user()->full_name }}</p>
                            <span class="d-block fs-13">{{ Auth::user()->roles->first()?->name ?? 'Staff' }}</span>
                        </div>
                    </div>

                    <a href="{{ route('admin.profile') }}" class="dropdown-item">
                        <i class="ti ti-user me-1 fs-17 align-middle"></i>
                        <span class="align-middle">My Profile</span>
                    </a>
                    @can('settings.manage')
                    <a href="{{ route('admin.settings.organization') }}" class="dropdown-item">
                        <i class="ti ti-settings me-1 fs-17 align-middle"></i>
                        <span class="align-middle">Settings</span>
                    </a>
                    @endcan

                    <div class="pt-2 mt-2 border-top">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="ti ti-logout me-1 fs-17 align-middle"></i>
                                <span class="align-middle">Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</header>
<!-- Topbar End -->

<!-- Search Modal -->
<div class="modal fade" id="searchModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-transparent">
            <div class="card shadow-none mb-0">
                <div class="px-3 py-2 d-flex flex-row align-items-center" id="search-top">
                    <i class="ti ti-search fs-22"></i>
                    <input type="search" class="form-control border-0" placeholder="Search">
                    <button type="button" class="btn p-0" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x fs-22"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
