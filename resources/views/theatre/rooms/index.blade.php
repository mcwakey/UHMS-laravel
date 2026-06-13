@extends('layouts.app')

@section('title', __('theatre.theatre_rooms'))

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
            <h3 class="mb-1">{{ __('theatre.theatre_rooms') }}</h3>
            <div class="text-muted small">{{ __('theatre.rooms_manage_hint') }}</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.calendar') }}">
                <i class="ti ti-calendar"></i> {{ __('theatre.calendar') }}
            </a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.index') }}">
                <i class="ti ti-list-details"></i> {{ __('theatre.board') }}
            </a>
            @can('theatre.rooms.create')
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRoomModal">
                    <i class="ti ti-plus"></i> {{ __('theatre.new_room') }}
                </button>
            @endcan
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.theatre.rooms.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">{{ __('common.search') }}</label>
                    <input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="{{ __('theatre.search_room_placeholder') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('common.type') }}</label>
                    <select name="room_type" class="form-select form-select-sm">
                        <option value="">{{ __('theatre.any_type') }}</option>
                        @foreach ($roomTypes as $type)
                            <option value="{{ $type->value }}" @selected(request('room_type') === $type->value)>{{ $type->translatedLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('common.status') }}</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">{{ __('theatre.any_status') }}</option>
                        @foreach ($roomStatuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->translatedLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('common.active') }}</label>
                    <select name="is_active" class="form-select form-select-sm">
                        <option value="">{{ __('theatre.any') }}</option>
                        <option value="1" @selected(request('is_active') === '1')>{{ __('common.active') }}</option>
                        <option value="0" @selected(request('is_active') === '0')>{{ __('common.inactive') }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-fill">{{ __('common.filter') }}</button>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.rooms.index') }}">{{ __('common.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('theatre.room') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('theatre.dept_location') }}</th>
                        <th>{{ __('theatre.capacity') }}</th>
                        <th>{{ __('theatre.open_cases') }}</th>
                        <th>{{ __('theatre.blocks') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rooms as $room)
                        <tr>
                            <td>
                                <strong>{{ $room->name }}</strong><br>
                                <small class="text-muted">{{ $room->code }}</small>
                                @unless($room->is_active)
                                    <span class="badge bg-dark ms-1">{{ __('common.inactive') }}</span>
                                @endunless
                            </td>
                            <td>{{ $room->room_type?->translatedLabel() ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $room->status?->color() ?? 'secondary' }}">
                                    {{ $room->status?->translatedLabel() ?? '-' }}
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
                                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.calendar', ['room_id' => $room->id]) }}" title="{{ __('theatre.view_schedule') }}">
                                        <i class="ti ti-calendar"></i>
                                    </a>
                                    @can('theatre.rooms.update')
                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editRoomModal{{ $room->id }}" title="{{ __('theatre.edit_room') }}">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#blockRoomModal{{ $room->id }}" title="{{ __('theatre.block_room') }}">
                                            <i class="ti ti-lock"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"><x-empty-state :message="__('theatre.no_theatre_rooms')" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $rooms->links() }}</div>
    </div>

    @if ($recentBlocks->isNotEmpty())
        <div class="card shadow-sm mt-3">
            <div class="card-header"><strong>{{ __('theatre.recent_room_blocks') }}</strong></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('theatre.room') }}</th>
                            <th>{{ __('common.type') }}</th>
                            <th>{{ __('theatre.window') }}</th>
                            <th>{{ __('common.reason') }}</th>
                            <th>{{ __('theatre.created_by') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentBlocks as $block)
                            <tr>
                                <td>{{ $block->theatreRoom?->name }}</td>
                                <td><x-status-badge :status="$block->block_type" /></td>
                                <td>{{ $block->start_at->format('d M H:i') }} - {{ $block->end_at->format('d M H:i') }}</td>
                                <td>{{ $block->reason }}</td>
                                <td>{{ $block->createdBy?->name ?? '-' }}</td>
                                <td class="text-end">
                                    @can('theatre.rooms.update')
                                        <form method="POST" action="{{ route('admin.theatre.rooms.blocks.destroy', $block) }}" onsubmit="return confirm('{{ __('theatre.remove_block_confirm') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}" class="btn btn-outline-danger btn-sm"><i class="ti ti-trash"></i></button>
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
                    <h5 class="modal-title">{{ __('theatre.new_theatre_room') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    @include('theatre.rooms.partials.form', ['room' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    <button class="btn btn-primary">{{ __('theatre.create_room') }}</button>
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
                        <h5 class="modal-title">{{ __('theatre.edit_room_title', ['name' => $room->name]) }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        @include('theatre.rooms.partials.form', ['room' => $room])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                        <button class="btn btn-primary">{{ __('common.save_changes') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="blockRoomModal{{ $room->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" method="POST" action="{{ route('admin.theatre.rooms.blocks.store', $room) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('theatre.block_room_title', ['name' => $room->name]) }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small">{{ __('theatre.block_type') }}</label>
                            <select name="block_type" class="form-select form-select-sm" required>
                                @foreach ($blockTypes as $type)
                                    <option value="{{ $type->value }}">{{ $type->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">{{ __('theatre.start') }}</label>
                                <input type="datetime-local" name="start_at" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">{{ __('theatre.end') }}</label>
                                <input type="datetime-local" name="end_at" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="form-label small">{{ __('common.reason') }}</label>
                            <input name="reason" class="form-control form-control-sm" required>
                        </div>
                        <div class="mt-2">
                            <label class="form-label small">{{ __('common.notes') }}</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                        <button class="btn btn-warning">{{ __('theatre.save_block') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach
@endsection
