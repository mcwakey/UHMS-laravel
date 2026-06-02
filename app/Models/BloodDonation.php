<?php

namespace App\Models;

use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodDonation extends Model
{
    use GeneratesNumbers, HasFactory;

    public const STATUS_COLLECTED = 'COLLECTED';

    public const STATUS_ACCEPTED = 'ACCEPTED';

    public const STATUS_REJECTED = 'REJECTED';

    public const SCREENING_PENDING = 'PENDING';

    public const SCREENING_PASSED = 'PASSED';

    public const SCREENING_FAILED = 'FAILED';

    public const SCREENING_INCONCLUSIVE = 'INCONCLUSIVE';

    protected $fillable = [
        'donation_number',
        'donor_id',
        'collected_by',
        'donation_date',
        'donation_type',
        'volume_ml',
        'blood_group',
        'screening_status',
        'screening_notes',
        'screened_by',
        'screened_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'donation_date' => 'datetime',
        'screened_at' => 'datetime',
    ];

    public function donor()
    {
        return $this->belongsTo(BloodDonor::class, 'donor_id');
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function screenedBy()
    {
        return $this->belongsTo(User::class, 'screened_by');
    }

    public function unit()
    {
        return $this->hasOne(BloodUnit::class, 'donation_id');
    }

    public function tests()
    {
        return $this->hasMany(BloodDonationTest::class, 'donation_id');
    }

    public function screening()
    {
        return $this->hasOne(BloodDonorScreening::class, 'donation_id');
    }

    public static function generateDonationNumber(): string
    {
        return static::generateNumber('BDN', 'blood_donations', 'donation_number');
    }
}
