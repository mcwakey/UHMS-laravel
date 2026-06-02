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
        'deferral_reason',
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

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public static function generateDonorNumber(): string
    {
        return static::generateNumber('BDR', 'blood_donors', 'donor_number');
    }
}
