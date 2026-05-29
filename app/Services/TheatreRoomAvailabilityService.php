<?php

namespace App\Services;

use App\Enums\ProcedureStatus;
use App\Models\ProcedureSchedule;
use App\Models\TheatreRoom;
use App\Models\TheatreRoomBlock;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class TheatreRoomAvailabilityService
{
    public function assertRoomCanBeScheduled(
        int $roomId,
        CarbonInterface|string $startAt,
        CarbonInterface|string $endAt,
        ?int $ignoreScheduleId = null,
        bool $override = false,
        ?string $overrideReason = null,
    ): void {
        $room = TheatreRoom::findOrFail($roomId);
        $start = $this->asCarbon($startAt);
        $end = $this->asCarbon($endAt);

        if ($end->lessThanOrEqualTo($start)) {
            throw new \InvalidArgumentException('Scheduled end time must be after the start time.');
        }

        if (! $room->is_active) {
            throw new \RuntimeException("{$room->name} is inactive and cannot be scheduled.");
        }

        if (! $room->isSchedulable()) {
            throw new \RuntimeException("{$room->name} is currently {$room->status?->label()} and cannot be scheduled.");
        }

        $block = $this->overlappingBlock($roomId, $start, $end);
        if ($block && ! $override) {
            throw new \RuntimeException(sprintf(
                '%s is blocked for %s from %s to %s. Choose another room or time.',
                $room->name,
                $block->block_type->label(),
                $block->start_at->format('H:i'),
                $block->end_at->format('H:i'),
            ));
        }

        $conflict = $this->overlappingSchedule($roomId, $start, $end, $ignoreScheduleId);
        if ($conflict && ! $override) {
            throw new \RuntimeException(sprintf(
                'Room already booked from %s to %s for %s. Choose another room or time.',
                $conflict->scheduled_start?->format('H:i'),
                $conflict->scheduled_end?->format('H:i'),
                $conflict->procedureRequest?->request_number ?? 'another procedure',
            ));
        }

        if (($block || $conflict) && $override && trim((string) $overrideReason) === '') {
            throw new \RuntimeException('Override reason is required when scheduling over a room conflict or block.');
        }
    }

    public function overlappingSchedule(
        int $roomId,
        CarbonInterface|string $startAt,
        CarbonInterface|string $endAt,
        ?int $ignoreScheduleId = null,
    ): ?ProcedureSchedule {
        $start = $this->asCarbon($startAt);
        $end = $this->asCarbon($endAt);
        $closed = array_map(fn (ProcedureStatus $status) => $status->value, ProcedureStatus::closedStatuses());

        return ProcedureSchedule::query()
            ->with('procedureRequest')
            ->where('theatre_room_id', $roomId)
            ->where('is_current', true)
            ->when($ignoreScheduleId, fn ($query) => $query->whereKeyNot($ignoreScheduleId))
            ->whereNotNull('scheduled_end')
            ->where('scheduled_start', '<', $end)
            ->where('scheduled_end', '>', $start)
            ->whereHas('procedureRequest', fn ($query) => $query->whereNotIn('status', $closed))
            ->orderBy('scheduled_start')
            ->first();
    }

    public function overlappingBlock(
        int $roomId,
        CarbonInterface|string $startAt,
        CarbonInterface|string $endAt,
    ): ?TheatreRoomBlock {
        $start = $this->asCarbon($startAt);
        $end = $this->asCarbon($endAt);

        return TheatreRoomBlock::query()
            ->where('theatre_room_id', $roomId)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->orderBy('start_at')
            ->first();
    }

    private function asCarbon(CarbonInterface|string $value): CarbonInterface
    {
        return $value instanceof CarbonInterface ? $value : Carbon::parse($value);
    }
}