@extends('layouts.app')

@section('title', 'Theatre Rooms')

@section('content')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Theatre Rooms</h3>
            <div class="text-muted small">Manage rooms, availability status, and maintenance or cleaning blocks.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.calendar') }}">
                <i class="ti ti-calendar"></i> Calendar
            </a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.index') }}">
                <i class="ti ti-list-details"></i> Board
            </a>
            @can('theatre.rooms.create')
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRoomModal">
                    <i class="ti ti-plus"></i> New Room
                </button>
            @endcan
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.theatre.rooms.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Search</label>
                    <input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Room name, code, location">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Type</label>
                    <select name="room_type" class="form-select form-select-sm">
                        <option value="">Any type</option>
                        @foreach ($roomTypes as $type)
                            <option value="{{ $type->value }}" @selected(request('room_type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Any status</option>
                        @foreach ($roomStatuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Active</label>
                    <select name="is_active" class="form-select form-select-sm">
                        <option value="">Any</option>
                        <option value="1" @selected(request('is_active') === '1')>Active</option>
                        <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-fill">Filter</button>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.rooms.index') }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Room</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Department / Location</th>
                        <th>Capacity</th>
                        <th>Open Cases</th>
                        <th>Blocks</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rooms as $room)
                        <tr>
                            <td>
                                <strong>{{ $room->name }}</strong><br>
                                <small class="text-muted">{{ $room->code }}</small>
                                @unless($room->is_active)
                                    <span class="badge bg-dark ms-1">Inactive</span>
                                @endunless
                            </td>
                            <td>{{ $room->room_type?->label() ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $room->status?->color() ?? 'secondary' }}">
                                    {{ $room->status?->label() ?? '-' }}
                                </span>
                            </td>
                            <td>
                                {{ $room->department?->name ?? '-' }}<br>
                                <small class="text-muted">{{ $room->location ?: '-' }}</small>
                            </td>
                            <td>{{ $room->capacity ?? '-' }}</td>
                            <td>{{ $room->open_schedules_count }}</td>
                            <td>{{ $room->active_blocks_count }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.calendar', ['room_id' => $room->id]) }}" title="View schedule">
                                        <i class="ti ti-calendar"></i>
                                    </a>
                                    @can('theatre.rooms.update')
                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editRoomModal{{ $room->id }}" title="Edit room">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#blockRoomModal{{ $room->id }}" title="Block room">
                                            <i class="ti ti-lock"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"><x-empty-state message="No theatre rooms found." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $rooms->links() }}</div>
    </div>

    @if ($recentBlocks->isNotEmpty())
        <div class="card shadow-sm mt-3">
            <div class="card-header"><strong>Recent Room Blocks</strong></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Room</th>
                            <th>Type</th>
                            <th>Window</th>
                            <th>Reason</th>
                            <th>Created By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentBlocks as $block)
                            <tr>
                                <td>{{ $block->theatreRoom?->name }}</td>
                                <td><span class="badge bg-{{ $block->block_type->color() }}">{{ $block->block_type->label() }}</span></td>
                                <td>{{ $block->start_at->format('d M H:i') }} - {{ $block->end_at->format('d M H:i') }}</td>
                                <td>{{ $block->reason }}</td>
                                <td>{{ $block->createdBy?->name ?? '-' }}</td>
                                <td class="text-end">
                                    @can('theatre.rooms.update')
                                        <form method="POST" action="{{ route('admin.theatre.rooms.blocks.destroy', $block) }}" onsubmit="return confirm('Remove this room block?')">
                                            @csrf
                                            @method('DELETE')
                                            <button aria-label="Delete" title="Delete" class="btn btn-outline-danger btn-sm"><i class="ti ti-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@can('theatre.rooms.create')
    <div class="modal fade" id="createRoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('admin.theatre.rooms.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">New Theatre Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('theatre.rooms.partials.form', ['room' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary">Create Room</button>
                </div>
            </form>
        </div>
    </div>
@endcan

@foreach ($rooms as $room)
    @can('theatre.rooms.update')
        <div class="modal fade" id="editRoomModal{{ $room->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('admin.theatre.rooms.update', $room) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit {{ $room->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('theatre.rooms.partials.form', ['room' => $room])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="blockRoomModal{{ $room->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" method="POST" action="{{ route('admin.theatre.rooms.blocks.store', $room) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Block {{ $room->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small">Block type</label>
                            <select name="block_type" class="form-select form-select-sm" required>
                                @foreach ($blockTypes as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Start</label>
                                <input type="datetime-local" name="start_at" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">End</label>
                                <input type="datetime-local" name="end_at" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="form-label small">Reason</label>
                            <input name="reason" class="form-control form-control-sm" required>
                        </div>
                        <div class="mt-2">
                            <label class="form-label small">Notes</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-warning">Save Block</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach
@endsection