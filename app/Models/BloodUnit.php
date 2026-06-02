<?php

namespace App\Models;

use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodUnit extends Model
{
    use GeneratesNumbers, HasFactory;

    public const STATUS_COLLECTED = 'COLLECTED';

    public const STATUS_QUARANTINED = 'QUARANTINED';

    public const STATUS_SCREENING_PENDING = 'SCREENING_PENDING';

    public const STATUS_AVAILABLE = 'AVAILABLE';

    public const STATUS_RESERVED = 'RESERVED';

    public const STATUS_CROSSMATCHED = 'CROSSMATCHED';

    public const STATUS_ISSUED = 'ISSUED';

    public const STATUS_TRANSFUSED = 'TRANSFUSED';

    public const STATUS_EXPIRED = 'EXPIRED';

    public const STATUS_DISCARDED = 'DISCARDED';

    public const STATUS_REJECTED = 'REJECTED';

    public const SCREENING_PENDING = 'PENDING';

    public const SCREENING_PASSED = 'PASSED';

    public const SCREENING_FAILED = 'FAILED';

    public const CROSSMATCH_COMPATIBLE = 'COMPATIBLE';

    public const CROSSMATCH_INCOMPATIBLE = 'INCOMPATIBLE';

    protected $fillable = [
        'unit_number',
        'donation_id',
        'donor_id',
        'blood_group',
        'component_type',
        'volume_ml',
        'collection_date',
        'expiry_date',
        'storage_location_id',
        'screening_status',
        'approved_by',
        'approved_at',
        'crossmatch_status',
        'status',
        'reserved_for_request_id',
        'issued_at',
        'discarded_at',
        'discarded_by',
        'discard_reason',
        'created_by',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'expiry_date' => 'date',
        'issued_at' => 'datetime',
        'discarded_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function donation()
    {
        return $this->belongsTo(BloodDonation::class, 'donation_id');
    }

    public function donor()
    {
        return $this->belongsTo(BloodDonor::class, 'donor_id');
    }

    public function storageLocation()
    {
        return $this->belongsTo(BloodStorageLocation::class, 'storage_location_id');
    }

    public function reservedForRequest()
    {
        return $this->belongsTo(BloodRequest::class, 'reserved_for_request_id');
    }

    public function crossmatches()
    {
        return $this->hasMany(BloodCrossmatch::class, 'blood_unit_id');
    }

    public function issue()
    {
        return $this->hasOne(BloodIssue::class, 'blood_unit_id');
    }

    public function discardedBy()
    {
        return $this->belongsTo(User::class, 'discarded_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date ? $this->expiry_date->lt(today()) : false;
    }

    public function daysToExpiry(): ?int
    {
        return $this->expiry_date ? (int) round(today()->floatDiffInDays($this->expiry_date, false)) : null;
    }

    public function isIssueable(): bool
    {
        return in_array($this->status, [self::STATUS_AVAILABLE, self::STATUS_RESERVED, self::STATUS_CROSSMATCHED], true)
            && $this->screening_status === self::SCREENING_PASSED
            && ! $this->isExpired();
    }

    /** Statuses that may never be issued, regardless of other checks. */
    public function isBlockedForIssue(): bool
    {
        return in_array($this->status, [
            self::STATUS_EXPIRED, self::STATUS_DISCARDED, self::STATUS_REJECTED,
            self::STATUS_ISSUED, self::STATUS_TRANSFUSED,
        ], true) || $this->isExpired();
    }

    public static function generateUnitNumber(): string
    {
        return static::generateNumber('BUN', 'blood_units', 'unit_number');
    }
}
