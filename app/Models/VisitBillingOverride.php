<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An authorised exception to the billing payment gate for a visit.
 *
 * The flagship type is DEFERRED_OPD_SETTLEMENT (render the whole OPD visit, pay
 * once at the end). See App\Services\Billing\VisitBillingOverrideService.
 */
class VisitBillingOverride extends Model
{
    use HasFactory;

    public const TYPE_DEFERRED_OPD_SETTLEMENT = 'DEFERRED_OPD_SETTLEMENT';
    public const TYPE_PAYMENT_GATE_BYPASS = 'PAYMENT_GATE_BYPASS';
    public const TYPE_CREDIT_APPROVAL = 'CREDIT_APPROVAL';
    public const TYPE_MANAGEMENT_APPROVAL = 'MANAGEMENT_APPROVAL';
    public const TYPE_INSURANCE_AUTHORIZATION_PENDING = 'INSURANCE_AUTHORIZATION_PENDING';
    /** Authorises an OPD visit to proceed despite the patient's PREVIOUS-visit debt. */
    public const TYPE_PREVIOUS_BALANCE_OVERRIDE = 'PREVIOUS_BALANCE_OVERRIDE';

    public const SCOPE_VISIT = 'VISIT';
    public const SCOPE_DEPARTMENT = 'DEPARTMENT';
    public const SCOPE_SERVICE = 'SERVICE';
    public const SCOPE_INVOICE_ITEM = 'INVOICE_ITEM';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_REVOKED = 'REVOKED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_COMPLETED = 'COMPLETED';

    protected $fillable = [
        'visit_id',
        'patient_id',
        'override_type',
        'scope',
        'scope_type',
        'scope_id',
        'reason',
        'authorized_by',
        'starts_at',
        'expires_at',
        'status',
        'created_by',
        'revoked_by',
        'revoked_at',
        'revoke_reason',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('override_type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Whether this override currently grants its exception (status ACTIVE and
     * within its start/expiry window).
     */
    public function isCurrentlyActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expires_at && $this->expires_at->isPast());
    }
}
