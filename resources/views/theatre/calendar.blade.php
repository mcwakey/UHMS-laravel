@extends('layouts.app')

@section('title', 'Theatre Room Calendar')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-1">Theatre Room Calendar</h3>
            <div class="text-muted small">Daily room schedule with procedure cases and room blocks.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.index') }}">
                <i class="ti ti-list-details"></i> Board
            </a>
            @can('theatre.rooms.view')
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.rooms.index') }}">
                    <i class="ti ti-door"></i> Rooms
                </a>
            @endcan
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.theatre.calendar') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate->toDateString() }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Room</label>
                    <select name="room_id" class="form-select form-select-sm">
                        <option value="">All rooms</option>
                        @foreach ($allRooms as $roomOption)
                            <option value="{{ $roomOption->id }}" @selected((int) $selectedRoomId === (int) $roomOption->id)>
                                {{ $roomOption->code }} - {{ $roomOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary btn-sm">Apply</button>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.calendar') }}">Today</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        @forelse ($rooms as $room)
            @php
                $roomSchedules = $schedulesByRoom->get($room->id, collect());
                $roomBlocks = $blocksByRoom->get($room->id, collect());
            @endphp
            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $room->name }}</strong>
                            <span class="text-muted small ms-1">{{ $room->code }}</span>
                        </div>
                        <span class="badge bg-{{ $room->status?->color() ?? 'secondary' }}">{{ $room->status?->label() ?? '-' }}</span>
                    </div>
                    <div class="card-body">
                        @if ($roomSchedules->isEmpty() && $roomBlocks->isEmpty())
                            <div class="text-muted small">No scheduled procedures or room blocks for this day.</div>
                        @else
                            <div class="vstack gap-2">
                                @foreach ($roomBlocks as $block)
                                    <div class="border rounded p-2 bg-light">
                                        <div class="d-flex justify-content-between">
                                            <strong>{{ $block->start_at->format('H:i') }} - {{ $block->end_at->format('H:i') }}</strong>
                                            <x-status-badge :status="$block->block_type" />
                                        </div>
                                        <div class="small">{{ $block->reason }}</div>
                                        @if ($block->notes)
                                            <div class="text-muted small">{{ $block->notes }}</div>
                                        @endif
                                    </div>
                                @endforeach
                                @foreach ($roomSchedules as $schedule)
                                    @php $procedure = $schedule->procedureRequest; @endphp
                                    <div class="border rounded p-2">
                                        <div class="d-flex justify-content-between gap-2">
                                            <strong>{{ $schedule->scheduled_start?->format('H:i') }} - {{ $schedule->scheduled_end?->format('H:i') }}</strong>
                                            @if ($procedure)
                                                <span class="badge" style="background-color: {{ $procedure->status->color() }}; color:#fff;">{{ $procedure->status->label() }}</span>
                                            @endif
                                        </div>
                                        @if ($procedure)
                                            <a href="{{ route('admin.theatre.show', $procedure) }}">{{ $procedure->request_number }}</a>
                                            <div class="small">
                                                {{ $procedure->patient?->first_name }} {{ $procedure->patient?->last_name }}
                                                - {{ $procedure->service?->name ?? 'Procedure' }}
                                            </div>
                                            <div class="text-muted small">
                                                Surgeon: {{ $schedule->surgeon?->name ?? '-' }} | Anaesthetist: {{ $schedule->anaesthetist?->name ?? '-' }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center text-muted py-4">No theatre rooms found for this filter.</div>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection