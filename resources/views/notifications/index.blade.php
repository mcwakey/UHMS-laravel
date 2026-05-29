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
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="card-title mb-0">All Notifications</h5>
                        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                            <select name="read" class="form-select form-select-sm" style="min-width:140px;" onchange="this.form.submit()">
                                <option value="" {{ $filterRead ? '' : 'selected' }}>All</option>
                                <option value="unread" {{ $filterRead === 'unread' ? 'selected' : '' }}>Unread</option>
                                <option value="read" {{ $filterRead === 'read' ? 'selected' : '' }}>Read</option>
                            </select>
                            <select name="module" class="form-select form-select-sm" style="min-width:160px;" onchange="this.form.submit()">
                                <option value="">All modules</option>
                                @foreach($moduleOptions as $mod)
                                    <option value="{{ $mod->value }}" {{ $filterModule === $mod->value ? 'selected' : '' }}>{{ $mod->label() }}</option>
                                @endforeach
                            </select>
                            <select name="priority" class="form-select form-select-sm" style="min-width:140px;" onchange="this.form.submit()">
                                <option value="">All priorities</option>
                                @foreach($priorityOptions as $pri)
                                    <option value="{{ $pri->value }}" {{ $filterPriority === $pri->value ? 'selected' : '' }}>{{ $pri->label() }}</option>
                                @endforeach
                            </select>
                            @if($filterRead || $filterModule || $filterPriority)
                                <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x me-1"></i>Reset</a>
                            @endif
                        </form>
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
                                    @php
                                        $data = is_array($notification->data) ? $notification->data : (array) $notification->data;
                                        $module = $data['module'] ?? null;
                                        $priority = $data['priority'] ?? null;
                                        $url = $data['action_url'] ?? ($data['url'] ?? '#');
                                        $title = $data['title'] ?? null;
                                    @endphp
                                    <a href="{{ $url }}" class="text-dark notification-page-item" data-id="{{ $notification->id }}">
                                        @if($title)
                                            <p class="mb-0 {{ is_null($notification->read_at) ? 'fw-semibold' : 'fw-medium' }}">{{ $title }}</p>
                                        @endif
                                        <p class="mb-1 fs-13 {{ is_null($notification->read_at) ? 'fw-semibold' : '' }}">
                                            {{ $data['message'] ?? 'Notification' }}
                                        </p>
                                    </a>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <small class="text-muted">
                                            <i class="ti ti-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                        </small>
                                        @if($module)
                                            <span class="badge bg-info-subtle text-info rounded-pill">{{ ucwords(strtolower(str_replace('_', ' ', $module))) }}</span>
                                        @endif
                                        @if($priority && $priority !== 'NORMAL')
                                            <span class="badge bg-{{ $data['color'] ?? 'primary' }}-subtle text-{{ $data['color'] ?? 'primary' }} rounded-pill">{{ ucfirst(strtolower($priority)) }}</span>
                                        @endif
                                        @if(is_null($notification->read_at))
                                            <span class="badge bg-primary-subtle text-primary rounded-pill">Unread</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ms-2 d-flex gap-1">
                                    @if(is_null($notification->read_at))
                                        <button class="btn btn-sm btn-link text-muted p-0 mark-read-btn" data-id="{{ $notification->id }}" title="Mark as read">
                                            <i class="ti ti-check fs-16"></i>
                                        </button>
                                    @endif
                                    <button class="btn btn-sm btn-link text-danger p-0 delete-notification-btn" data-id="{{ $notification->id }}" title="Delete">
                                        <i class="ti ti-trash fs-16"></i>
                                    </button>
                                </div>
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

    // Delete a notification
    $('.delete-notification-btn').on('click', function() {
        var btn = $(this);
        var id = btn.data('id');
        if (! confirm('Delete this notification?')) return;
        $.ajax({
            url: '{{ url("admin/notifications") }}/' + id,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
                btn.closest('.d-flex.align-items-start').remove();
            }
        });
    });
});
</script>
@endpush
