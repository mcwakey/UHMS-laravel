<?php

namespace App\Http\Controllers\Theatre;

use App\Enums\ProcedureStatus;
use App\Enums\TheatreRoomBlockType;
use App\Enums\TheatreRoomStatus;
use App\Enums\TheatreRoomType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\TheatreRoom;
use App\Models\TheatreRoomBlock;
use App\Services\TheatreRoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TheatreRoomController extends Controller
{
    public function __construct(protected TheatreRoomService $rooms) {}

    public function index(Request $request)
    {
        $closed = array_map(fn (ProcedureStatus $status) => $status->value, ProcedureStatus::closedStatuses());

        $query = TheatreRoom::query()
            ->with('department')
            ->withCount([
                'schedules as open_schedules_count' => fn ($schedule) => $schedule
                    ->where('is_current', true)
                    ->whereHas('procedureRequest', fn ($procedure) => $procedure->whereNotIn('status', $closed)),
                'blocks as active_blocks_count' => fn ($block) => $block->where('end_at', '>', now()),
            ]);

        if ($search = $request->input('search')) {
            $query->where(function ($room) use ($search) {
                $room->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('room_type')) {
            $query->where('room_type', $type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rooms = $query->orderBy('name')->paginate(20)->withQueryString();
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $roomTypes = TheatreRoomType::cases();
        $roomStatuses = TheatreRoomStatus::cases();
        $blockTypes = TheatreRoomBlockType::cases();
        $recentBlocks = TheatreRoomBlock::with(['theatreRoom', 'createdBy'])
            ->where('end_at', '>=', now()->subDay())
            ->latest('start_at')
            ->take(10)
            ->get();

        return view('theatre.rooms.index', compact(
            'rooms', 'departments', 'roomTypes', 'roomStatuses', 'blockTypes', 'recentBlocks'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validateRoom($request);

        try {
            $this->rooms->create($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Theatre room created.');
    }

    public function update(Request $request, TheatreRoom $theatreRoom)
    {
        $data = $this->validateRoom($request, $theatreRoom);

        try {
            $this->rooms->update($theatreRoom, $data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Theatre room updated.');
    }

    public function status(Request $request, TheatreRoom $theatreRoom)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in($this->enumValues(TheatreRoomStatus::cases()))],
        ]);

        try {
            $this->rooms->setStatus($theatreRoom, $data['status']);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Theatre room status updated.');
    }

    public function storeBlock(Request $request, TheatreRoom $theatreRoom)
    {
        $data = $request->validate([
            'block_type' => ['required', Rule::in($this->enumValues(TheatreRoomBlockType::cases()))],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->rooms->createBlock($theatreRoom, $data, Auth::user());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Theatre room block recorded.');
    }

    public function destroyBlock(TheatreRoomBlock $block)
    {
        $block->delete();

        return back()->with('success', 'Theatre room block removed.');
    }

    private function validateRoom(Request $request, ?TheatreRoom $room = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('theatre_rooms', 'code')->ignore($room?->id)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'room_type' => ['required', Rule::in($this->enumValues(TheatreRoomType::cases()))],
            'capacity' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in($this->enumValues(TheatreRoomStatus::cases()))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function enumValues(array $cases): array
    {
        return array_map(fn ($case) => $case->value, $cases);
    }
}