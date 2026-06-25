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
            <a aria-label="Menu deep" title="Menu deep" id="mobile_btn" class="mobile-btn" href="#sidebar">
                <i class="ti ti-menu-deep fs-24"></i>
            </a>

            <button aria-label="Next" title="Next" class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn2">
                <i class="ti ti-arrow-right"></i>
            </button>

            <!-- Search -->
            <div class="me-auto d-flex align-items-center header-search d-lg-flex d-none">
                <div class="input-icon-start position-relative me-2">
                    <span class="input-icon-addon">
                        <i class="ti ti-search"></i>
                    </span>
                    <input type="text" class="form-control shadow-sm" placeholder="{{ __('common.search') }}">
                    <span class="input-icon-addon text-dark shadow fs-18 d-inline-flex p-0 header-search-icon"><i class="ti ti-command"></i></span>
                </div>
            </div>

        </div>

        <div class="d-flex align-items-center">

            @can('queue.view')
            @unless(request()->boolean('embedded'))
            <div class="header-item d-none d-md-flex me-2">
                <button type="button"
                        class="topbar-link btn btn-icon"
                        aria-label="{{ __('menu.queue_board') }}"
                        title="{{ __('menu.queue_board') }}"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#queueBoardTopbarPanel">
                    <i class="ti ti-list-numbers fs-16"></i>
                </button>
            </div>
            @endunless
            @endcan

            <!-- Search for Mobile -->
            <div class="header-item d-flex d-lg-none me-2">
                <button aria-label="Search" title="Search" class="topbar-link btn btn-icon" data-bs-toggle="modal" data-bs-target="#searchModal" type="button">
                    <i class="ti ti-search fs-16"></i>
                </button>
            </div>

            <!-- Light/Dark Mode Button -->
            <div class="header-item d-none d-sm-flex me-2">
                <button aria-label="Moon" title="Moon" class="topbar-link btn btn-icon topbar-link" id="light-dark-mode" type="button">
                    <i class="ti ti-moon fs-16"></i>
                </button>
            </div>

            <!-- Notification Dropdown -->
            <div class="header-item">
                <div class="dropdown me-3">
                    <button class="topbar-link btn btn-icon topbar-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown" data-bs-offset="0,24" type="button" aria-haspopup="false" aria-expanded="false" id="notificationDropdownBtn">
                        <i class="ti ti-bell-check fs-16 animate-ring"></i>
                        <span class="notification-badge" id="notificationBadge" style="display:none;"></span>
                    </button>
                    <div class="dropdown-menu p-0 dropdown-menu-end dropdown-menu-lg notification-dropdown-menu">
                        <div class="p-2 border-bottom">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0 fs-16 fw-semibold">{{ __('common.notifications') }}</h6>
                                </div>
                                <div class="col-auto">
                                    <a href="javascript:void(0);" class="text-muted fs-13" id="markAllReadBtn" style="display:none;">
                                        {{ __('common.mark_all_read') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="notification-body position-relative z-2 rounded-0" data-simplebar id="notificationList">
                            <div class="text-center text-muted py-4" id="noNotifications">
                                <i class="ti ti-bell-off fs-24 d-block mb-2"></i>
                                {{ __('common.no_new_notifications') }}
                            </div>
                        </div>
                        <div class="p-2 border-top text-center">
                            <a href="{{ route('admin.notifications.index') }}" class="text-primary fs-13">
                                {{ __('common.view_all_notifications') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @include('layouts.partials.language-switcher')

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
                            <span class="d-block fs-13">{{ Auth::user()->roles->first()?->name ?? __('common.staff') }}</span>
                        </div>
                    </div>

                    <a href="{{ route('admin.profile') }}" class="dropdown-item">
                        <i class="ti ti-user me-1 fs-17 align-middle"></i>
                        <span class="align-middle">{{ __('common.my_profile') }}</span>
                    </a>
                    @can('settings.manage')
                    <a href="{{ route('admin.settings.organization') }}" class="dropdown-item">
                        <i class="ti ti-settings me-1 fs-17 align-middle"></i>
                        <span class="align-middle">{{ __('common.settings') }}</span>
                    </a>
                    @endcan

                    <div class="pt-2 mt-2 border-top">
                        <p class="fs-12 text-muted mb-1 px-2">{{ __('common.language') }}</p>
                        <form method="POST" action="{{ route('locale.switch') }}" data-spa-ignore="true" class="mb-0">
                            @csrf
                            <input type="hidden" name="locale" value="en">
                            <button type="submit" class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                                <i class="ti ti-language me-1 fs-17 align-middle"></i>
                                <span class="align-middle">English</span>
                                @if(app()->getLocale() === 'en')<i class="ti ti-check ms-auto float-end mt-1"></i>@endif
                            </button>
                        </form>
                        <form method="POST" action="{{ route('locale.switch') }}" data-spa-ignore="true" class="mb-0">
                            @csrf
                            <input type="hidden" name="locale" value="fr">
                            <button type="submit" class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}">
                                <i class="ti ti-language me-1 fs-17 align-middle"></i>
                                <span class="align-middle">Français</span>
                                @if(app()->getLocale() === 'fr')<i class="ti ti-check ms-auto float-end mt-1"></i>@endif
                            </button>
                        </form>
                    </div>

                    <div class="pt-2 mt-2 border-top">
                        <form method="POST" action="{{ route('logout') }}" data-spa-ignore="true">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="ti ti-logout me-1 fs-17 align-middle"></i>
                                <span class="align-middle">{{ __('common.log_out') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</header>
<!-- Topbar End -->

@can('queue.view')
@unless(request()->boolean('embedded'))
<div class="offcanvas offcanvas-end" tabindex="-1" id="queueBoardTopbarPanel" aria-labelledby="queueBoardTopbarPanelLabel" style="width: min(920px, 100vw);">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="queueBoardTopbarPanelLabel">
            <i class="ti ti-list-numbers me-1"></i>{{ __('menu.queue_board') }}
        </h5>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.queue.board') }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                <i class="ti ti-external-link me-1"></i>{{ __('common.open') }}
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('common.close') }}"></button>
        </div>
    </div>
    <div class="offcanvas-body p-0">
        <div id="queueBoardTopbarContent" class="p-3" data-url="{{ route('admin.queue.board', ['embedded' => 1]) }}">
            <div class="text-center text-muted py-5">
                <span class="spinner-border spinner-border-sm me-2"></span>{{ __('common.loading') }}
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const panel = document.getElementById('queueBoardTopbarPanel');
    const content = document.getElementById('queueBoardTopbarContent');
    if (!panel || !content) return;

    async function loadQueueBoard(force) {
        if (content.dataset.loaded === '1' && !force) return;

        content.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>{{ __('common.loading') }}</div>';

        try {
            const response = await fetch(content.dataset.url, {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const html = await response.text();
            const parsed = new DOMParser().parseFromString(html, 'text/html');
            const board = parsed.getElementById('queueBoardContent');

            if (!response.ok || !board) {
                throw new Error('Unable to load queue board.');
            }

            content.innerHTML = board.innerHTML;
            content.dataset.loaded = '1';
        } catch (error) {
            content.innerHTML = '<div class="alert alert-danger m-3">Unable to load queue board.</div>';
        }
    }

    panel.addEventListener('show.bs.offcanvas', function () {
        loadQueueBoard(false);
    });

    content.addEventListener('click', function (event) {
        const refreshButton = event.target.closest('[data-queue-board-refresh]');
        if (!refreshButton) return;

        event.preventDefault();
        loadQueueBoard(true);
    });
});
</script>
@endpush
@endunless
@endcan

<!-- Search Modal -->
<div class="modal fade" id="searchModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-transparent">
            <div class="card shadow-none mb-0">
                <div class="px-3 py-2 d-flex flex-row align-items-center" id="search-top">
                    <i class="ti ti-search fs-22"></i>
                    <input type="search" class="form-control border-0" placeholder="{{ __('common.search') }}">
                    <button type="button" class="btn p-0" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x fs-22"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
