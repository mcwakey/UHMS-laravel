<?php

namespace App\Services\FrontDesk;

use App\Enums\FrontDesk\LostFoundStatus;
use App\Enums\LogModule;
use App\Models\FrontDeskLostFoundItem;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Validation\ValidationException;

/**
 * Lost & found workflow (Phase 18E). Non-clinical; notes are kept in-column and
 * phone numbers are masked on display.
 */
class LostFoundService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function create(array $data, User $actor): FrontDeskLostFoundItem
    {
        $data['created_by'] = $actor->id;
        $data['item_status'] = $data['item_status'] ?? LostFoundStatus::FOUND->value;
        $data['found_or_reported_at'] = $data['found_or_reported_at'] ?? now();
        if (empty($data['reference_number'])) {
            $data['reference_number'] = FrontDeskLostFoundItem::generateReferenceNumber();
        }

        $item = FrontDeskLostFoundItem::create($data);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_LOST_FOUND_CREATED', $this->context($item), $item);

        return $item;
    }

    public function update(FrontDeskLostFoundItem $item, array $data, User $actor): FrontDeskLostFoundItem
    {
        $data['updated_by'] = $actor->id;
        // Never regenerate the reference number on update.
        unset($data['reference_number'], $data['created_by']);

        $item->fill($data)->save();

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_LOST_FOUND_UPDATED', $this->context($item), $item);

        return $item->refresh();
    }

    public function markClaimed(FrontDeskLostFoundItem $item, array $data, User $actor): FrontDeskLostFoundItem
    {
        if ($item->isClosed()) {
            throw ValidationException::withMessages(['status' => __('front_desk.errors.item_closed')]);
        }

        $item->update([
            'item_status' => LostFoundStatus::CLAIMED->value,
            'claimed_by_name' => $data['claimed_by_name'] ?? $item->claimed_by_name,
            'claimed_by_phone' => $data['claimed_by_phone'] ?? $item->claimed_by_phone,
            'claim_verified_by' => $actor->id,
            'claimed_at' => now(),
            'updated_by' => $actor->id,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_LOST_FOUND_CLAIMED', $this->context($item), $item);

        return $item;
    }

    public function release(FrontDeskLostFoundItem $item, array $data, User $actor): FrontDeskLostFoundItem
    {
        if ($item->item_status === LostFoundStatus::CANCELLED) {
            throw ValidationException::withMessages(['status' => __('front_desk.errors.item_cancelled')]);
        }
        if ($item->isReleased()) {
            throw ValidationException::withMessages(['status' => __('front_desk.errors.item_already_released')]);
        }

        $item->update([
            'item_status' => LostFoundStatus::RELEASED->value,
            'claimed_by_name' => $data['claimed_by_name'] ?? $item->claimed_by_name,
            'claimed_by_phone' => $data['claimed_by_phone'] ?? $item->claimed_by_phone,
            'released_by' => $actor->id,
            'released_at' => now(),
            'updated_by' => $actor->id,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_LOST_FOUND_RELEASED', $this->context($item), $item);

        return $item;
    }

    public function cancel(FrontDeskLostFoundItem $item, User $actor, ?string $reason = null): FrontDeskLostFoundItem
    {
        $metadata = $item->metadata ?? [];
        if ($reason !== null && trim($reason) !== '') {
            $metadata['cancellation_reason'] = trim($reason);
        }

        $item->update([
            'item_status' => LostFoundStatus::CANCELLED->value,
            'updated_by' => $actor->id,
            'metadata' => $metadata ?: null,
        ]);

        $this->activityLog->log(LogModule::FRONT_DESK, 'FRONT_DESK_LOST_FOUND_CANCELLED', $this->context($item), $item);

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function context(FrontDeskLostFoundItem $item): array
    {
        return [
            'metadata' => array_filter([
                'front_desk_lost_found_item_id' => $item->id,
                'reference_number' => $item->reference_number,
                'item_status' => $item->item_status?->value,
                'item_category' => $item->item_category?->value,
            ], fn ($v) => $v !== null),
        ];
    }
}
