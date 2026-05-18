@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="content-page">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Notifications</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Notifications</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">All Notifications</h5>
                        @if($notifications->where('read_at', null)->count() > 0)
                            <button class="btn btn-sm btn-outline-primary" id="markAllReadPageBtn">
                                <i class="ti ti-checks me-1"></i>Mark All as Read
                            </button>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @forelse($notifications as $notification)
                            <div class="d-flex align-items-start p-3 border-bottom {{ is_null($notification->read_at) ? 'bg-light-subtle' : '' }}">
                                <div class="flex-shrink-0 me-3">
                                    <span class="avatar avatar-sm bg-{{ $notification->data['color'] ?? 'primary' }}-subtle rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="ti {{ $notification->data['icon'] ?? 'ti-bell' }} text-{{ $notification->data['color'] ?? 'primary' }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <a href="{{ $notification->data['url'] ?? '#' }}" class="text-dark notification-page-item" data-id="{{ $notification->id }}">
                                        <p class="mb-1 {{ is_null($notification->read_at) ? 'fw-semibold' : '' }}">
                                            {{ $notification->data['message'] ?? 'Notification' }}
                                        </p>
                                    </a>
                                    <div class="d-flex align-items-center gap-2">
                                        <small class="text-muted">
                                            <i class="ti ti-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                        </small>
                                        @if($notification->data['type'] ?? false)
                                            <span class="badge bg-{{ $notification->data['color'] ?? 'primary' }}-subtle text-{{ $notification->data['color'] ?? 'primary' }} rounded-pill">
                                                {{ ucfirst(str_replace('_', ' ', $notification->data['type'])) }}
                                            </span>
                                        @endif
                                        @if(is_null($notification->read_at))
                                            <span class="badge bg-primary-subtle text-primary rounded-pill">Unread</span>
                                        @endif
                                    </div>
                                </div>
                                @if(is_null($notification->read_at))
                                    <div class="flex-shrink-0 ms-2">
                                        <button class="btn btn-sm btn-link text-muted p-0 mark-read-btn" data-id="{{ $notification->id }}" title="Mark as read">
                                            <i class="ti ti-check fs-16"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">
                                <i class="ti ti-bell-off fs-48 d-block mb-3"></i>
                                <h6>No notifications yet</h6>
                                <p class="mb-0">You'll see notifications here when there's activity in the system.</p>
                            </div>
                        @endforelse
                    </div>
                    @if($notifications->hasPages())
                        <div class="card-footer">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Mark single notification as read
    $('.mark-read-btn').on('click', function() {
        var btn = $(this);
        var id = btn.data('id');
        $.ajax({
            url: '{{ url("admin/notifications") }}/' + id + '/read',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
                btn.closest('.d-flex.align-items-start').removeClass('bg-light-subtle');
                btn.remove();
            }
        });
    });

    // Mark single as read on link click
    $('.notification-page-item').on('click', function() {
        var id = $(this).data('id');
        $.ajax({
            url: '{{ url("admin/notifications") }}/' + id + '/read',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' }
        });
    });

    // Mark all as read
    $('#markAllReadPageBtn').on('click', function() {
        $.ajax({
            url: '{{ route("admin.notifications.mark-all-read") }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
                if (window.UhmsInertia) {
                    window.UhmsInertia.reload({ preserveScroll: true });
                } else {
                    location.reload();
                }
            }
        });
    });
});
</script>
@endpush
