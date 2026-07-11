<?php

namespace App\Models;

use App\Enums\FrontDesk\LostFoundCategory;
use App\Enums\FrontDesk\LostFoundStatus;
use App\Models\Concerns\MasksFrontDeskPhone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Front Desk lost & found item (Phase 18E). Non-clinical register of items
 * found / reported lost. Phone numbers are masked for display.
 */
class FrontDeskLostFoundItem extends Model
{
    use HasFactory, MasksFrontDeskPhone;

    protected $fillable = [
        'reference_number', 'item_status', 'item_category', 'item_description',
        'found_or_reported_at', 'found_location', 'found_by_name',
        'reported_by_name', 'reported_by_phone', 'stored_location',
        'claimed_by_name', 'claimed_by_phone', 'claim_verified_by', 'claimed_at',
        'released_by', 'released_at', 'disposal_note', 'notes',
        'created_by', 'updated_by', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'item_status' => LostFoundStatus::class,
            'item_category' => LostFoundCategory::class,
            'found_or_reported_at' => 'datetime',
            'claimed_at' => 'datetime',
            'released_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /* ── Relationships ───────────────────────────────────────────── */

    public function claimVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claim_verified_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ── Scopes ──────────────────────────────────────────────────── */

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('found_or_reported_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('found_or_reported_at', '<=', $to);
        }

        return $query;
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('item_status', $status) : $query;
    }

    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        return $category ? $query->where('item_category', $category) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('reference_number', 'like', "%{$term}%")
                ->orWhere('item_description', 'like', "%{$term}%")
                ->orWhere('found_by_name', 'like', "%{$term}%")
                ->orWhere('reported_by_name', 'like', "%{$term}%")
                ->orWhere('claimed_by_name', 'like', "%{$term}%")
                ->orWhere('stored_location', 'like', "%{$term}%");
        });
    }

    /** Items still awaiting a claim (found or reported lost). */
    public function scopeUnclaimed(Builder $query): Builder
    {
        return $query->whereIn('item_status', [LostFoundStatus::FOUND->value, LostFoundStatus::REPORTED_LOST->value]);
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    public function isUnclaimed(): bool
    {
        return in_array($this->item_status, [LostFoundStatus::FOUND, LostFoundStatus::REPORTED_LOST], true);
    }

    public function isReleased(): bool
    {
        return $this->item_status === LostFoundStatus::RELEASED;
    }

    public function isClosed(): bool
    {
        return in_array($this->item_status, [LostFoundStatus::RELEASED, LostFoundStatus::DISPOSED, LostFoundStatus::CANCELLED], true);
    }

    public function maskedReportedPhone(): ?string
    {
        return $this->maskPhone($this->reported_by_phone);
    }

    public function maskedClaimedPhone(): ?string
    {
        return $this->maskPhone($this->claimed_by_phone);
    }

    public static function generateReferenceNumber(): string
    {
        $prefix = (string) config('front_desk.lost_found.reference_prefix', 'LF');
        $date = now()->format('Ymd');
        $last = static::where('reference_number', 'like', "{$prefix}-{$date}-%")->orderByDesc('reference_number')->value('reference_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $seq);
    }
}
