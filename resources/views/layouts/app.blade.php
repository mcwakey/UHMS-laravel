<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'UHMS') - {{ config('app.name') }}</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/img/favicon.png') }}">

    <!-- Theme Config -->
    <script src="{{ URL::asset('build/js/theme-script.js') }}"></script>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/fontawesome/css/all.min.css') }}">

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="{{ URL::asset('build/plugins/tabler-icons/tabler-icons.min.css') }}">

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

    <!-- Template Style -->
    <link rel="stylesheet" href="{{ URL::asset('build/css/style.css') }}">

    {{-- Anti-FOUC: hide page until critical CSS is parsed.
         Prevents the sidebar/menu "flash of unstyled content" on load. --}}
    <style>
        html.uhms-loading body { visibility: hidden; }
        #sidebar { transition: none !important; }
        /* keep sidebar dimensions reserved while JS initializes */
        .sidebar-menu ul { list-style: none; padding-left: 0; margin: 0; }
        .sidebar-menu .menu-title { opacity: 0.7; }
    </style>
    <script>document.documentElement.classList.add('uhms-loading');</script>

    @stack('styles')
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
            </div>
        </div>
        <!-- /Page Content -->

        <!-- Footer -->
        <div class="footer text-center bg-white p-2 border-top">
            <p class="text-dark mb-0">
                <script>document.write(new Date().getFullYear())</script> &copy;
                <a href="javascript:void(0);" class="link-primary">UHMS</a> - Ultimate Hospital Management System
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

    <!-- Template Script -->
    <script src="{{ URL::asset('build/js/script.js') }}"></script>

    <!--UHMS_LEGACY_SCRIPTS_START-->
    <!-- Notification Polling -->
    @auth
    <script>
    (function() {
        const POLL_INTERVAL = 30000; // 30 seconds
        const badge = document.getElementById('notificationBadge');
        const list = document.getElementById('notificationList');
        const noNotif = document.getElementById('noNotifications');
        const markAllBtn = document.getElementById('markAllReadBtn');

        if (!badge || !list || !noNotif || !markAllBtn) {
            return;
        }

        if (window.uhmsNotificationInterval) {
            clearInterval(window.uhmsNotificationInterval);
        }

        function fetchNotifications() {
            $.ajax({
                url: '{{ route("admin.notifications.recent") }}',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Update badge
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
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
                            html += '<a href="' + n.url + '" class="dropdown-item px-3 py-2 notification-item" data-id="' + n.id + '">' +
                                '<div class="d-flex align-items-start">' +
                                '<div class="flex-shrink-0 me-2">' +
                                '<span class="avatar avatar-sm bg-' + n.color + '-subtle rounded-circle d-flex align-items-center justify-content-center">' +
                                '<i class="ti ' + n.icon + ' text-' + n.color + '"></i></span></div>' +
                                '<div class="flex-grow-1">' +
                                '<p class="mb-0 fs-13">' + $('<span>').text(n.message).html() + '</p>' +
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
</body>
</html>
