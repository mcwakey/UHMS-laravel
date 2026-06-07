<?php

namespace App\Models;

use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BloodDonor extends Model
{
    use GeneratesNumbers, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_DEFERRED = 'DEFERRED';

    public const STATUS_INACTIVE = 'INACTIVE';

    // WHO screening lifecycle status
    public const SCREENING_REGISTERED = 'REGISTERED';

    public const SCREENING_QUESTIONNAIRE_PENDING = 'QUESTIONNAIRE_PENDING';

    public const SCREENING_PHYSICAL_PENDING = 'PHYSICAL_ASSESSMENT_PENDING';

    public const SCREENING_ELIGIBILITY_PENDING = 'ELIGIBILITY_PENDING';

    public const SCREENING_ELIGIBLE = 'ELIGIBLE';

    public const SCREENING_TEMP_DEFERRED = 'TEMPORARILY_DEFERRED';

    public const SCREENING_PERM_DEFERRED = 'PERMANENTLY_DEFERRED';

    protected $fillable = [
        'donor_number',
        'patient_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'blood_group',
        'phone',
        'email',
        'address',
        'last_donation_at',
        'status',
        'screening_status',
        'deferral_reason',
        'deferral_type',
        'deferred_until',
        'registered_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_donation_at' => 'datetime',
        'deferred_until' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function donations()
    {
        return $this->hasMany(BloodDonation::class, 'donor_id');
    }

    public function units()
    {
        return $this->hasMany(BloodUnit::class, 'donor_id');
    }

    public function screenings()
    {
        return $this->hasMany(BloodDonorScreening::class, 'donor_id');
    }

    public function latestScreening()
    {
        return $this->hasOne(BloodDonorScreening::class, 'donor_id')->latestOfMany();
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /** Whether a donor is currently within an active deferral window. */
    public function isDeferred(): bool
    {
        if ($this->status === self::STATUS_DEFERRED) {
            if ($this->deferred_until) {
                return $this->deferred_until->gte(today());
            }

            return true;
        }

        return in_array($this->screening_status, [
            self::SCREENING_TEMP_DEFERRED,
            self::SCREENING_PERM_DEFERRED,
        ], true);
    }

    public function canDonate(): bool
    {
        return $this->screening_status === self::SCREENING_ELIGIBLE && ! $this->isDeferred();
    }

    public static function generateDonorNumber(): string
    {
        return static::generateNumber('BDR', 'blood_donors', 'donor_number');
    }
}
