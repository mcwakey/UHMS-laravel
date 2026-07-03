<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'UHMS') - {{ config('app.name') }}</title>

    @include('layouts.partials.frame-breaker')

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/img/favicon.png') }}">

    {{-- PWA --}}
    <meta name="theme-color" content="#0d6efd">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="UHMS">
    <link rel="apple-touch-icon" href="{{ URL::asset('build/img/favicon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

    <!-- Theme Config -->
    <script src="{{ URL::asset('build/js/theme-script.js') }}"></script>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/fontawesome/css/all.min.css') }}">

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/tabler-icons/tabler-icons.min.css') }}?v={{ filemtime(public_path('build/plugins/tabler-icons/tabler-icons.min.css')) }}">

    <!-- Simplebar CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/simplebar/simplebar.min.css') }}">

    <!-- Daterangepicker CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/daterangepicker/daterangepicker.css') }}">

    <!-- Datetimepicker CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap-datetimepicker.min.css') }}">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/dataTables.bootstrap5.min.css') }}">

    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/select2/css/select2.min.css') }}">

    <!-- SweetAlert2 CSS — required: the loaded sweetalert2.min.js does NOT inject its
         own styles, so without this every confirm dialog renders unstyled at the page
         bottom (the "malformed/near-invisible confirmation"). -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/sweetalert2/sweetalert2.min.css') }}">

    <!-- Template Style -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/style.css') }}">

    <!-- UHMS Design Rules -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/uhms-design-system.css') }}?v={{ filemtime(public_path('build/css/uhms-design-system.css')) }}">

    {{-- Anti-FOUC: hide page until critical CSS is parsed.
         Prevents the sidebar/menu "flash of unstyled content" on load. --}}
    <style>
        html.uhms-loading body { visibility: hidden; }
        #sidebar { transition: none !important; }
        /* keep sidebar dimensions reserved while JS initializes */
        .sidebar-menu ul { list-style: none; padding-left: 0; margin: 0; }
        .sidebar-menu .menu-title { opacity: 0.7; }
        .notification-dropdown-menu {
            width: min(480px, calc(100vw - 1.5rem)) !important;
            max-width: calc(100vw - 1.5rem) !important;
            min-height: 300px;
            overflow: hidden;
        }
        .notification-dropdown-menu .notification-body {
            width: 100%;
            max-height: min(420px, calc(100vh - 220px));
            overflow-x: hidden;
            overflow-y: auto;
        }
        .notification-dropdown-menu .simplebar-content-wrapper,
        .notification-dropdown-menu .simplebar-mask {
            width: 100% !important;
            max-height: min(420px, calc(100vh - 220px));
            overflow-x: hidden !important;
        }
        .notification-dropdown-menu .simplebar-content {
            min-width: 0 !important;
            width: 100% !important;
        }
        .notification-dropdown-menu .notification-item {
            display: block;
            width: 100% !important;
            min-width: 0;
            max-width: 100% !important;
            overflow: hidden !important;
            white-space: normal !important;
        }
        .notification-dropdown-menu .notification-item .d-flex {
            min-width: 0;
            max-width: 100%;
            width: 100%;
        }
        .notification-dropdown-menu .notification-item .flex-shrink-0 {
            width: 42px;
        }
        .notification-dropdown-menu .notification-content {
            flex-basis: 0;
            min-width: 0;
            max-width: calc(100% - 42px);
            overflow: hidden !important;
        }
        .notification-dropdown-menu .notification-title,
        .notification-dropdown-menu .notification-message {
            display: block;
            max-width: 100%;
            line-height: 1.35;
            overflow-wrap: anywhere;
            word-break: break-word;
            white-space: normal;
        }
        .notification-dropdown-menu .badge {
            max-width: 100%;
            white-space: normal;
            overflow-wrap: anywhere;
        }
    </style>
    <script>document.documentElement.classList.add('uhms-loading');</script>

    <!--UHMS_LEGACY_STYLES_START-->
    @stack('styles')
    <!--UHMS_LEGACY_STYLES_END-->
</head>
<body>
    <!--UHMS_LEGACY_LAYOUT-->
    <!--UHMS_LEGACY_BODY_START-->
    <div class="main-wrapper">

        @include('layouts.partials.header')
        @include('layouts.partials.sidebar')

        <!-- Page Content -->
        <div class="page-wrapper">
            <div class="content">

                {{-- Flash Messages --}}
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ti ti-circle-check me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ti ti-alert-circle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @yield('content')
                @include('layouts.partials.flash-dedupe')
            </div>
        </div>
        <!-- /Page Content -->

        <!-- Footer -->
        <div class="footer text-center bg-white p-2 border-top">
            <p class="text-dark mb-0">
                <script>document.write(new Date().getFullYear())</script> &copy;
                <a href="javascript:void(0);" class="link-primary">UHMS</a> - {{ __('common.app_tagline') }}
            </p>
        </div>

    </div>
    <!--UHMS_LEGACY_BODY_END-->

    <!-- jQuery -->
    <script src="{{ URL::asset('build/js/jquery-3.7.1.min.js') }}"></script>

    <!-- Bootstrap JS -->
    <script src="{{ URL::asset('build/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Simplebar JS -->
    <script src="{{ URL::asset('build/plugins/simplebar/simplebar.min.js') }}"></script>

    <!-- Daterangepicker JS -->
    <script src="{{ URL::asset('build/js/moment.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/daterangepicker/daterangepicker.js') }}"></script>

    <!-- Datetimepicker JS -->
    <script src="{{ URL::asset('build/js/bootstrap-datetimepicker.min.js') }}"></script>

    <!-- DataTables JS -->
    <script src="{{ URL::asset('build/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/dataTables.bootstrap5.min.js') }}"></script>

    <!-- Select2 JS -->
    <script src="{{ URL::asset('build/plugins/select2/js/select2.min.js') }}"></script>

    <!-- SweetAlert2 -->
    <script src="{{ URL::asset('build/plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    <!-- Chart.js -->
    <script src="{{ URL::asset('build/plugins/chartjs/chart.min.js') }}"></script>

    <!-- Global i18n bridge — read by script.js and other standalone JS files -->
    @php
    $uhmsI18n = [
        'today'        => __('common.drp_today'),
        'yesterday'    => __('common.drp_yesterday'),
        'last_7_days'  => __('common.drp_last_7_days'),
        'last_30_days' => __('common.drp_last_30_days'),
        'this_month'   => __('common.drp_this_month'),
        'last_month'   => __('common.drp_last_month'),
        'this_year'    => __('common.drp_this_year'),
        'last_year'    => __('common.drp_last_year'),
        'next_year'    => __('common.drp_next_year'),
        'clear'        => __('common.drp_clear'),
        'search'       => __('common.search'),
        'dt_search_placeholder' => __('common.search'),
        'dt_info'      => __('common.drp_dt_info'),
        'dt_length'    => __('common.drp_dt_length'),
    ];
    @endphp
    <script>window.UHMS_I18N = @json($uhmsI18n);</script>

    <!-- Template Script -->
    <script src="{{ URL::asset('build/js/script.js') }}"></script>

    <!--UHMS_LEGACY_SCRIPTS_START-->
    <script>
    (function() {
        const storageKey = '__THEME_CONFIG__';
        const html = document.documentElement;

        function readThemeConfig() {
            const baseConfig = Object.assign({}, window.defaultConfig || {}, window.config || {});

            try {
                const storedConfig = JSON.parse(sessionStorage.getItem(storageKey) || '{}');
                return Object.assign(baseConfig, storedConfig || {});
            } catch (error) {
                return baseConfig;
            }
        }

        function writeThemeConfig(theme) {
            const config = readThemeConfig();
            config.theme = theme;
            window.config = Object.assign({}, window.config || {}, config);

            try {
                sessionStorage.setItem(storageKey, JSON.stringify(config));
            } catch (error) {
                // Session storage can be disabled by the browser; the DOM update still works.
            }
        }

        function updateThemeButton(theme) {
            const button = document.getElementById('light-dark-mode');
            const icon = button ? button.querySelector('i') : null;

            if (!button || !icon) {
                return;
            }

            const darkMode = theme === 'dark';
            icon.classList.toggle('ti-moon', !darkMode);
            icon.classList.toggle('ti-sun', darkMode);
            button.setAttribute('aria-label', darkMode ? 'Light mode' : 'Dark mode');
            button.setAttribute('title', darkMode ? 'Light mode' : 'Dark mode');
        }

        function applyTheme(theme) {
            const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
            html.setAttribute('data-bs-theme', normalizedTheme);
            writeThemeConfig(normalizedTheme);
            updateThemeButton(normalizedTheme);
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (html.hasAttribute('data-enable-theme-customizer')) {
                return;
            }

            const button = document.getElementById('light-dark-mode');
            const currentTheme = html.getAttribute('data-bs-theme') || readThemeConfig().theme || 'light';

            applyTheme(currentTheme);

            if (!button || button.dataset.uhmsThemeBound === 'true') {
                return;
            }

            button.dataset.uhmsThemeBound = 'true';
            button.addEventListener('click', function() {
                const activeTheme = html.getAttribute('data-bs-theme') || readThemeConfig().theme || 'light';
                applyTheme(activeTheme === 'dark' ? 'light' : 'dark');
            });
        });
    })();
    </script>

    <!-- Notification Polling -->
    @auth
    <script>
    (function() {
        const POLL_INTERVAL = 30000; // 30 seconds
        const badge = document.getElementById('notificationBadge');
        const list = document.getElementById('notificationList');
        const noNotif = document.getElementById('noNotifications');
        const markAllBtn = document.getElementById('markAllReadBtn');
        let lastUnreadCount = null;
        let audioUnlocked = false;
        let audioContext = null;

        if (!badge || !list || !noNotif || !markAllBtn) {
            return;
        }

        if (window.uhmsNotificationInterval) {
            clearInterval(window.uhmsNotificationInterval);
        }

        function unlockNotificationAudio() {
            if (audioUnlocked) {
                return;
            }

            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) {
                    return;
                }

                audioContext = audioContext || new AudioContext();
                if (audioContext.state === 'suspended') {
                    audioContext.resume();
                }
                audioUnlocked = true;
            } catch (error) {
                audioUnlocked = false;
            }
        }

        function playNotificationBeep() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                audioContext = audioContext || (AudioContext ? new AudioContext() : null);

                if (!audioContext || audioContext.state === 'suspended') {
                    return;
                }

                const now = audioContext.currentTime;
                const gain = audioContext.createGain();
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(0.18, now + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.28);
                gain.connect(audioContext.destination);

                [880, 1175].forEach(function(frequency, index) {
                    const osc = audioContext.createOscillator();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(frequency, now + (index * 0.11));
                    osc.connect(gain);
                    osc.start(now + (index * 0.11));
                    osc.stop(now + 0.18 + (index * 0.11));
                });
            } catch (error) {
                // Audio is best-effort; notification polling should never fail because of it.
            }
        }

        document.addEventListener('click', unlockNotificationAudio, { once: true, passive: true });
        document.addEventListener('keydown', unlockNotificationAudio, { once: true });

        function fetchNotifications() {
            $.ajax({
                url: '{{ route("admin.notifications.recent") }}',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const unreadCount = Number(data.unread_count || 0);
                    if (lastUnreadCount !== null && unreadCount > lastUnreadCount) {
                        playNotificationBeep();
                    }
                    lastUnreadCount = unreadCount;

                    // Update badge
                    if (unreadCount > 0) {
                        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                        badge.style.display = '';
                        markAllBtn.style.display = '';
                    } else {
                        badge.style.display = 'none';
                        markAllBtn.style.display = 'none';
                    }

                    // Update dropdown list
                    if (data.notifications.length > 0) {
                        noNotif.style.display = 'none';
                        var html = '';
                        data.notifications.forEach(function(n) {
                            var modBadge = n.module ? '<span class="badge bg-light text-dark border me-1 fs-11">' + $('<span>').text(n.module).html() + '</span>' : '';
                            var prioBadge = (n.priority && n.priority !== 'NORMAL') ? '<span class="badge bg-' + n.color + ' me-1 fs-11">' + $('<span>').text(n.priority).html() + '</span>' : '';
                            var title = n.title ? '<div class="fw-semibold fs-13 mb-0 notification-title">' + $('<span>').text(n.title).html() + '</div>' : '';
                            html += '<a href="' + n.url + '" class="dropdown-item px-3 py-2 notification-item" data-id="' + n.id + '">' +
                                '<div class="d-flex align-items-start">' +
                                '<div class="flex-shrink-0 me-2">' +
                                '<span class="avatar avatar-sm bg-' + n.color + '-subtle rounded-circle d-flex align-items-center justify-content-center">' +
                                '<i class="ti ' + n.icon + ' text-' + n.color + '"></i></span></div>' +
                                '<div class="flex-grow-1 notification-content">' +
                                title +
                                '<div class="mb-1">' + modBadge + prioBadge + '</div>' +
                                '<p class="mb-0 fs-13 notification-message">' + $('<span>').text(n.message).html() + '</p>' +
                                '<span class="fs-12 text-muted">' + $('<span>').text(n.time).html() + '</span>' +
                                '</div></div></a>';
                        });
                        // Keep noNotif element, prepend items before it
                        $(list).find('.notification-item').remove();
                        $(noNotif).before(html);
                    } else {
                        $(list).find('.notification-item').remove();
                        noNotif.style.display = '';
                    }
                }
            });
        }

        // Mark single as read on click
        $(document).off('click.uhmsNotifications', '.notification-item').on('click.uhmsNotifications', '.notification-item', function() {
            var id = $(this).data('id');
            $.ajax({
                url: '{{ url("admin/notifications") }}/' + id + '/read',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' }
            });
        });

        // Mark all as read
        $(markAllBtn).off('click.uhmsNotifications').on('click.uhmsNotifications', function(e) {
            e.preventDefault();
            $.ajax({
                url: '{{ route("admin.notifications.mark-all-read") }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function() {
                    fetchNotifications();
                }
            });
        });

        // Initial fetch + polling
        fetchNotifications();
        window.uhmsNotificationInterval = setInterval(fetchNotifications, POLL_INTERVAL);
    })();
    </script>
    @endauth

    @stack('scripts')
    @yield('scripts')
    <!--UHMS_LEGACY_SCRIPTS_END-->

    {{-- Global double-submit protection: on a real (non-prevented) form submit,
         disable the submit button(s) and show a spinner so a quick double-click
         cannot create duplicate payments/dispenses/stock movements. Opt out with
         data-no-loading on the form. A 12s safety net re-enables the button. --}}
    <script>
        (function () {
            document.addEventListener('submit', function (event) {
                var form = event.target;
                if (event.defaultPrevented) return;                 // cancelled (confirm returned false, etc.)
                if (!form || form.hasAttribute('data-no-loading')) return;

                var buttons = form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]');
                buttons.forEach(function (btn) {
                    if (btn.dataset.uhmsLoading === '1' || btn.disabled) return;
                    btn.dataset.uhmsLoading = '1';
                    if (btn.tagName === 'BUTTON') {
                        btn.dataset.uhmsOriginal = btn.innerHTML;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>'
                            + (btn.getAttribute('data-loading-text') || @json(__('common.please_wait')));
                    }
                    btn.disabled = true;
                });

                // Safety net: re-enable if the navigation never happens (validation blocked, etc.).
                setTimeout(function () {
                    buttons.forEach(function (btn) {
                        if (btn.dataset.uhmsLoading !== '1') return;
                        btn.disabled = false;
                        btn.dataset.uhmsLoading = '';
                        if (typeof btn.dataset.uhmsOriginal === 'string') btn.innerHTML = btn.dataset.uhmsOriginal;
                    });
                }, 12000);
            }, false);

            // bfcache restore (back button): clear any stuck loading state.
            window.addEventListener('pageshow', function () {
                document.querySelectorAll('[data-uhms-loading="1"]').forEach(function (btn) {
                    btn.disabled = false;
                    btn.dataset.uhmsLoading = '';
                    if (typeof btn.dataset.uhmsOriginal === 'string') btn.innerHTML = btn.dataset.uhmsOriginal;
                });
            });
        }());
    </script>

    <script>
        (function () {
            function cleanupModalState(force) {
                if (!force) {
                    var openModals = document.querySelectorAll('.modal.show');
                    if (openModals.length > 0) {
                        return;
                    }
                }

                // Force-hide any lingering modal instances first (Inertia nav case).
                if (force && window.bootstrap && bootstrap.Modal) {
                    document.querySelectorAll('.modal').forEach(function (el) {
                        var inst = bootstrap.Modal.getInstance(el);
                        if (inst) { try { inst.hide(); inst.dispose(); } catch (e) {} }
                        el.classList.remove('show');
                        el.style.display = '';
                        el.removeAttribute('aria-modal');
                        el.setAttribute('aria-hidden', 'true');
                    });
                }

                document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                    backdrop.remove();
                });

                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }

            function forceCleanup() { cleanupModalState(true); }

            window.uhmsCleanupModalState = cleanupModalState;
            window.uhmsForceCleanupModals = forceCleanup;

            document.addEventListener('DOMContentLoaded', cleanupModalState);
            document.addEventListener('hidden.bs.modal', function () {
                // Defer so Bootstrap finishes its own teardown first.
                setTimeout(cleanupModalState, 50);
            });
            // Inertia navigations destroy modal DOM without firing hidden.bs.modal.
            document.addEventListener('inertia:before', forceCleanup);
            document.addEventListener('inertia:navigate', forceCleanup);
            document.addEventListener('inertia:success', forceCleanup);
            window.addEventListener('pageshow', cleanupModalState);
            window.addEventListener('popstate', forceCleanup);
            window.addEventListener('beforeunload', forceCleanup);

            // Delegated dismiss button — covers dynamically-added modals.
            document.addEventListener('click', function (event) {
                var trigger = event.target.closest('[data-bs-dismiss="modal"]');
                if (!trigger) return;
                var modalEl = trigger.closest('.modal');
                if (!modalEl) return;
                if (window.bootstrap && bootstrap.Modal) {
                    var inst = bootstrap.Modal.getOrCreateInstance(modalEl);
                    try { inst.hide(); } catch (e) {}
                }
                setTimeout(cleanupModalState, 250);
            });

            document.addEventListener('submit', function (event) {
                var modalEl = event.target.closest('.modal.show');
                if (!modalEl || event.defaultPrevented) {
                    return;
                }

                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                }

                setTimeout(cleanupModalState, 200);
            }, true);
        }());
    </script>

    {{-- Persist the sidebar scroll position across full page reloads.
         Strategy: scroll the active menu item into view (so the position is
         layout-independent), and use a raw-pixel fallback for pages with no
         active item. Saves on every scroll event (throttled) and on navigation. --}}
    <script>
        (function () {
            var SCROLL_KEY = 'uhmsSidebarScrollTop';
            var HREF_KEY   = 'uhmsSidebarActiveHref';

            function scroller() {
                var inner = document.querySelector('#sidebar .sidebar-inner');
                if (!inner) return null;
                return inner.querySelector('.simplebar-content-wrapper') || inner;
            }

            function save() {
                var el = scroller();
                if (!el) return;
                try {
                    sessionStorage.setItem(SCROLL_KEY, String(el.scrollTop));
                    // Also stash the href of the active leaf link so we can find
                    // the right item even if a different submenu opened.
                    var activeLink = document.querySelector('#sidebar a.active[href]:not([href="javascript:void(0);"])');
                    if (activeLink) sessionStorage.setItem(HREF_KEY, activeLink.href);
                } catch (e) {}
            }

            // Bind the scroll-save listener as soon as SimpleBar has the wrapper.
            var saveTimer = null;
            function bindScroll() {
                var el = scroller();
                if (!el || el.__uhmsScrollBound) return;
                el.__uhmsScrollBound = true;
                el.addEventListener('scroll', function () {
                    if (saveTimer) return;
                    saveTimer = setTimeout(function () { saveTimer = null; save(); }, 150);
                }, { passive: true });
            }

            // Save before any sidebar link navigates away.
            document.addEventListener('click', function (e) {
                var a = e.target.closest('#sidebar a[href]');
                if (a && a.href && a.href.indexOf('javascript:') === -1) save();
            }, true);
            window.addEventListener('beforeunload', save);

            // Restore: prefer scrolling the active item into view; fall back to pixel.
            var restoredPixel = null;
            var restoredHref  = null;
            try {
                var v = sessionStorage.getItem(SCROLL_KEY);
                if (v !== null) restoredPixel = parseFloat(v) || 0;
                restoredHref = sessionStorage.getItem(HREF_KEY);
            } catch (e) {}

            var userInteracted = false;
            ['wheel', 'touchstart', 'keydown', 'mousedown'].forEach(function (evt) {
                document.addEventListener(evt, function (e) {
                    if (e.target && e.target.closest && e.target.closest('#sidebar')) userInteracted = true;
                }, { passive: true, capture: true });
            });

            var tries = 0;
            var iv = setInterval(function () {
                tries++;
                bindScroll();

                if (!userInteracted) {
                    var el = scroller();
                    if (el) {
                        // Try to scroll the active item into view first.
                        var activeItem = document.querySelector('#sidebar li.active > a.active[href]:not([href="javascript:void(0);"])');
                        if (!activeItem && restoredHref) {
                            // Match by href in case the active class hasn't been set yet.
                            try {
                                activeItem = document.querySelector('#sidebar a[href="' + CSS.escape(restoredHref) + '"]');
                            } catch (ex) {}
                        }

                        if (activeItem) {
                            // scrollIntoView on the scroller's coordinate space so the
                            // item is visible roughly in the centre of the sidebar.
                            var itemTop    = activeItem.getBoundingClientRect().top;
                            var scrollerRect = el.getBoundingClientRect();
                            var offset = itemTop - scrollerRect.top - (scrollerRect.height / 2) + (activeItem.offsetHeight / 2);
                            if (Math.abs(offset) > 2) {
                                el.scrollTop += offset;
                            }
                            // Once the item is roughly in view, stop retrying.
                            clearInterval(iv);
                            return;
                        }

                        // Fallback: raw pixel restore.
                        if (restoredPixel !== null && Math.abs(el.scrollTop - restoredPixel) > 1) {
                            el.scrollTop = restoredPixel;
                        }
                    }
                }

                if (userInteracted || tries > 60) clearInterval(iv);
            }, 50);
        }());
    </script>

    {{-- Reveal page once everything has loaded — kills the sidebar FOUC --}}
    <script>
        window.addEventListener('load', function () {
            document.documentElement.classList.remove('uhms-loading');
        });
        // Safety net: never leave the page hidden longer than 1.5s
        setTimeout(function () {
            document.documentElement.classList.remove('uhms-loading');
        }, 1500);
    </script>
    <script src="{{ asset('register-sw.js') }}?v={{ filemtime(public_path('register-sw.js')) }}" defer></script>
</body>
</html>
