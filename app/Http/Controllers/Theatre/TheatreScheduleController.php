<?php

namespace App\Http\Controllers\Theatre;

use App\Http\Controllers\Controller;
use App\Models\ProcedureSchedule;
use App\Models\TheatreRoom;
use App\Models\TheatreRoomBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TheatreScheduleController extends Controller
{
    public function calendar(Request $request)
    {
        $selectedDate = Carbon::parse($request->input('date', today()->toDateString()))->startOfDay();
        $start = $selectedDate->copy()->startOfDay();
        $end = $selectedDate->copy()->endOfDay();
        $selectedRoomId = $request->integer('room_id') ?: null;

        $rooms = TheatreRoom::query()
            ->when($selectedRoomId, fn ($query) => $query->whereKey($selectedRoomId))
            ->orderBy('name')
            ->get();

        $schedules = ProcedureSchedule::query()
            ->with([
                'theatreRoom',
                'surgeon',
                'anaesthetist',
                'procedureRequest.patient',
                'procedureRequest.visit',
                'procedureRequest.service',
            ])
            ->where('is_current', true)
            ->whereNotNull('theatre_room_id')
            ->where('scheduled_start', '<=', $end)
            ->where('scheduled_end', '>=', $start)
            ->when($selectedRoomId, fn ($query) => $query->where('theatre_room_id', $selectedRoomId))
            ->orderBy('scheduled_start')
            ->get();

        $blocks = TheatreRoomBlock::query()
            ->with('theatreRoom')
            ->where('start_at', '<=', $end)
            ->where('end_at', '>=', $start)
            ->when($selectedRoomId, fn ($query) => $query->where('theatre_room_id', $selectedRoomId))
            ->orderBy('start_at')
            ->get();

        $allRooms = TheatreRoom::orderBy('name')->get(['id', 'name', 'code']);
        $schedulesByRoom = $schedules->groupBy('theatre_room_id');
        $blocksByRoom = $blocks->groupBy('theatre_room_id');

        return view('theatre.calendar', compact(
            'selectedDate', 'selectedRoomId', 'rooms', 'allRooms', 'schedulesByRoom', 'blocksByRoom'
        ));
    }
}