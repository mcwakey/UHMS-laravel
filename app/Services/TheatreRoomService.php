<?php

namespace App\Services;

use App\Enums\ProcedureStatus;
use App\Enums\TheatreRoomBlockType;
use App\Enums\TheatreRoomStatus;
use App\Models\TheatreRoom;
use App\Models\TheatreRoomBlock;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class TheatreRoomService
{
    public function create(array $data): TheatreRoom
    {
        return TheatreRoom::create($this->roomPayload($data));
    }

    public function update(TheatreRoom $room, array $data): TheatreRoom
    {
        if (array_key_exists('is_active', $data) && ! $data['is_active'] && $this->hasOpenSchedules($room)) {
            throw new \RuntimeException('Room has open theatre cases and cannot be deactivated. Set room status instead or clear active schedules first.');
        }

        $room->fill($this->roomPayload($data, partial: true))->save();

        return $room->fresh(['department']);
    }

    public function setStatus(TheatreRoom $room, TheatreRoomStatus|string $status): TheatreRoom
    {
        $status = $status instanceof TheatreRoomStatus ? $status : TheatreRoomStatus::from($status);
        $room->forceFill(['status' => $status])->save();

        return $room->fresh();
    }

    public function createBlock(TheatreRoom $room, array $data, ?User $user): TheatreRoomBlock
    {
        if (! $room->is_active) {
            throw new \RuntimeException('Inactive rooms cannot receive new schedule blocks.');
        }

        return DB::transaction(function () use ($room, $data, $user) {
            $block = TheatreRoomBlock::create([
                'theatre_room_id' => $room->id,
                'block_type' => TheatreRoomBlockType::from($data['block_type'])->value,
                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'],
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $user?->id,
            ]);

            if ($this->blockIsCurrent($block->start_at, $block->end_at)) {
                $room->forceFill(['status' => $this->statusForBlock($block->block_type)->value])->save();
            }

            return $block->fresh(['theatreRoom', 'createdBy']);
        });
    }

    public function hasOpenSchedules(TheatreRoom $room): bool
    {
        $closed = array_map(fn (ProcedureStatus $status) => $status->value, ProcedureStatus::closedStatuses());

        return $room->schedules()
            ->where('is_current', true)
            ->whereHas('procedureRequest', fn ($query) => $query->whereNotIn('status', $closed))
            ->exists();
    }

    private function roomPayload(array $data, bool $partial = false): array
    {
        $fields = [
            'name',
            'code',
            'department_id',
            'room_type',
            'capacity',
            'location',
            'status',
            'notes',
            'is_active',
        ];

        $payload = [];
        foreach ($fields as $field) {
            if (! $partial || array_key_exists($field, $data)) {
                $payload[$field] = $data[$field] ?? null;
            }
        }

        if (array_key_exists('code', $payload) && is_string($payload['code'])) {
            $payload['code'] = strtoupper(trim($payload['code']));
        }
        if (array_key_exists('room_type', $payload) && $payload['room_type']) {
            $payload['room_type'] = $payload['room_type'] instanceof \BackedEnum ? $payload['room_type']->value : $payload['room_type'];
        }
        if (array_key_exists('status', $payload) && $payload['status']) {
            $payload['status'] = $payload['status'] instanceof \BackedEnum ? $payload['status']->value : $payload['status'];
        }

        return $payload;
    }

    private function blockIsCurrent(CarbonInterface $startAt, CarbonInterface $endAt): bool
    {
        return $startAt->lessThanOrEqualTo(now()) && $endAt->greaterThan(now());
    }

    private function statusForBlock(TheatreRoomBlockType $blockType): TheatreRoomStatus
    {
        return match ($blockType) {
            TheatreRoomBlockType::CLEANING, TheatreRoomBlockType::STERILIZATION => TheatreRoomStatus::CLEANING,
            TheatreRoomBlockType::MAINTENANCE, TheatreRoomBlockType::EQUIPMENT_FAILURE => TheatreRoomStatus::MAINTENANCE,
            TheatreRoomBlockType::RESERVED_EMERGENCY_SLOT => TheatreRoomStatus::RESERVED,
            TheatreRoomBlockType::OTHER => TheatreRoomStatus::RESERVED,
        };
    }
}