<?php

namespace App\Models;

use App\Enums\FrontDesk\CourierDirection;
use App\Enums\FrontDesk\CourierHandoverStatus;
use App\Enums\FrontDesk\CourierStatus;
use App\Enums\FrontDesk\CourierType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Front Desk courier log (Phase 18A). Incoming / outgoing letters, parcels,
 * documents, reports, invoices, supplies, etc. Patient link is optional and the
 * clinical contents of an item are never exposed.
 */
class FrontDeskCourierLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'direction',
        'courier_type',
        'sender_name',
        'sender_organization',
        'recipient_name',
        'recipient_department_id',
        'related_patient_id',
        'related_visit_id',
        'courier_company',
        'messenger_name',
        'tracking_number',
        'reference_number',
        'received_or_sent_at',
        'received_by',
        'sent_by',
        'delivered_to',
        'delivered_at',
        'status',
        'handover_status',
        'dispatch_department_id',
        'dispatched_at',
        'dispatched_by',
        'received_internally_by',
        'proof_reference',
        'signature_required',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'direction' => CourierDirection::class,
            'courier_type' => CourierType::class,
            'status' => CourierStatus::class,
            'handover_status' => CourierHandoverStatus::class,
            'received_or_sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'signature_required' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function recipientDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'recipient_department_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'related_patient_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'related_visit_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function dispatchDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dispatch_department_id');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receivedInternallyBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_internally_by');
    }

    public function handoffs(): HasMany
    {
        return $this->hasMany(FrontDeskCourierHandoff::class, 'courier_log_id')->orderBy('action_at')->orderBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('sender_name', 'like', "%{$term}%")
                ->orWhere('sender_organization', 'like', "%{$term}%")
                ->orWhere('recipient_name', 'like', "%{$term}%")
                ->orWhere('courier_company', 'like', "%{$term}%")
                ->orWhere('tracking_number', 'like', "%{$term}%")
                ->orWhere('reference_number', 'like', "%{$term}%")
                ->orWhereHas('patient', function (Builder $pq) use ($term) {
                    $pq->where('patient_number', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                });
        });
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('received_or_sent_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('received_or_sent_at', '<=', $to);
        }

        return $query;
    }

    public function scopeDirection(Builder $query, ?string $direction): Builder
    {
        return $direction ? $query->where('direction', $direction) : $query;
    }

    public function scopeCourierType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('courier_type', $type) : $query;
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeDepartment(Builder $query, ?int $departmentId): Builder
    {
        return $departmentId ? $query->where('recipient_department_id', $departmentId) : $query;
    }

    /** Items still awaiting action (received / pending dispatch / dispatched). */
    public function scopePendingCourier(Builder $query): Builder
    {
        return $query->whereIn('status', CourierStatus::pendingValues());
    }

    public function scopeHandoverStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('handover_status', $status) : $query;
    }

    /** Items awaiting dispatch: received or pending_dispatch, not yet dispatched. */
    public function scopePendingDispatch(Builder $query): Builder
    {
        return $query->whereIn('status', [CourierStatus::RECEIVED->value, CourierStatus::PENDING_DISPATCH->value])
            ->whereNull('dispatched_at');
    }

    public function scopeInTransit(Builder $query): Builder
    {
        return $query->where('handover_status', CourierHandoverStatus::IN_TRANSIT->value);
    }

    public function scopeAwaitingHandover(Builder $query): Builder
    {
        return $query->where('handover_status', CourierHandoverStatus::AWAITING_HANDOVER->value);
    }

    public function scopeReturnedItems(Builder $query): Builder
    {
        return $query->where('status', CourierStatus::RETURNED->value);
    }

    /** Dispatched/in-transit items older than the configured overdue threshold. */
    public function scopeOverdueCourier(Builder $query, ?int $hours = null): Builder
    {
        $hours = $hours ?? (int) config('front_desk.couriers.overdue_hours', 24);

        return $query->where('handover_status', CourierHandoverStatus::IN_TRANSIT->value)
            ->whereNotNull('dispatched_at')
            ->where('dispatched_at', '<', now()->subHours($hours));
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isDelivered(): bool
    {
        return $this->status === CourierStatus::DELIVERED;
    }

    public function isPending(): bool
    {
        return in_array($this->status->value, CourierStatus::pendingValues(), true);
    }

    public function isOverdueCourier(): bool
    {
        $hours = (int) config('front_desk.couriers.overdue_hours', 24);

        return $this->handover_status === CourierHandoverStatus::IN_TRANSIT
            && $this->dispatched_at !== null
            && $this->dispatched_at->lt(now()->subHours($hours));
    }

    public function deliveryNote(): ?string
    {
        return $this->metadata['delivery_note'] ?? null;
    }
}
