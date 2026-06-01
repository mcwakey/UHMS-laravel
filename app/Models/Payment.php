<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use HasFactory, SoftDeletes, GeneratesNumbers, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'payment_method', 'reference_number'])
            ->logOnlyDirty()
            ->useLogName('billing')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'payment_number',
        'invoice_id',
        'patient_id',
        'amount',
        'payment_method',
        'reference_number',
        'received_by',
        'notes',
        'paid_at',
        'status',
        'is_reversal',
        'reversed_payment_id',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'paid_at' => 'datetime',
            'status' => PaymentStatus::class,
            'is_reversal' => 'boolean',
            'reversed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /**
     * The reversal payment that voided this one (if any).
     */
    public function reversal()
    {
        return $this->hasOne(Payment::class, 'reversed_payment_id');
    }

    /**
     * The original payment this row reverses (when is_reversal = true).
     */
    public function originalPayment()
    {
        return $this->belongsTo(Payment::class, 'reversed_payment_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', PaymentStatus::ACTIVE->value);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedAmountAttribute(): string
    {
        return '₵' . number_format($this->amount, 2);
    }

    public function getIsReversedAttribute(): bool
    {
        return ($this->status instanceof PaymentStatus ? $this->status : PaymentStatus::tryFrom((string) $this->status))
            === PaymentStatus::REVERSED;
    }

    public function getCanReverseAttribute(): bool
    {
        return ! $this->is_reversal
            && ! $this->is_reversed
            && (float) $this->amount > 0;
    }
}
