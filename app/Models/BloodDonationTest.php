<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodDonationTest extends Model
{
    use HasFactory;

    public const RESULT_NEGATIVE = 'NEGATIVE';

    public const RESULT_POSITIVE = 'POSITIVE';

    public const RESULT_REACTIVE = 'REACTIVE';

    public const RESULT_NON_REACTIVE = 'NON_REACTIVE';

    public const RESULT_INCONCLUSIVE = 'INCONCLUSIVE';

    public const RESULT_NOT_DONE = 'NOT_DONE';

    /** Results that fail a unit. */
    public const FAILING_RESULTS = [self::RESULT_POSITIVE, self::RESULT_REACTIVE];

    /** Results that clear a unit. */
    public const PASSING_RESULTS = [self::RESULT_NEGATIVE, self::RESULT_NON_REACTIVE];

    protected $fillable = [
        'donation_id', 'test_code', 'test_name', 'mandatory', 'result',
        'performed_by', 'performed_at', 'verified_by', 'verified_at', 'notes',
    ];

    protected $casts = [
        'mandatory' => 'boolean',
        'performed_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function donation()
    {
        return $this->belongsTo(BloodDonation::class, 'donation_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isFailing(): bool
    {
        return in_array($this->result, self::FAILING_RESULTS, true);
    }

    public function isPassing(): bool
    {
        return in_array($this->result, self::PASSING_RESULTS, true);
    }
}
