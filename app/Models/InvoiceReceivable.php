<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InvoiceReceivable extends Model
{
    use HasFactory, LogsActivity;

    public const PAYER_PATIENT = 'patient';
    public const PAYER_INSURANCE = 'insurance';
    public const PAYER_SPONSOR = 'sponsor';
    public const PAYER_CORPORATE = 'corporate';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_WRITTEN_OFF = 'written_off';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_id',
        'patient_id',
        'visit_id',
        'payer_type',
        'payer_id',
        'original_amount',
        'allocated_amount',
        'paid_amount',
        'discount_amount',
        'credit_note_amount',
        'write_off_amount',
        'refund_amount',
        'balance',
        'aging_start_date',
        'due_date',
        'status',
        'insurance_provider_id',
        'sponsor_id',
        'corporate_client_id',
        'claim_id',
        'sponsor_authorization_id',
        'corporate_account_id',
        'journal_entry_id',
        'accounting_status',
        'accounting_posted_at',
        'accounting_error',
        'allocation_source',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'allocated_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'credit_note_amount' => 'decimal:2',
            'write_off_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'aging_start_date' => 'date',
            'due_date' => 'date',
            'accounting_posted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['payer_type', 'payer_id', 'allocated_amount', 'paid_amount', 'balance', 'status'])
            ->logOnlyDirty()
            ->useLogName('billing')
            ->dontSubmitEmptyLogs();
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function corporateClient()
    {
        return $this->belongsTo(CorporateClient::class);
    }

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }

    public function sponsorAuthorization()
    {
        return $this->belongsTo(SponsorAuthorization::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_PAID,
            self::STATUS_WRITTEN_OFF,
            self::STATUS_CANCELLED,
        ])->where('balance', '>', 0);
    }

    public function payerName(): string
    {
        return match ($this->payer_type) {
            self::PAYER_INSURANCE => $this->insuranceProvider?->name ?? 'Insurance payer',
            self::PAYER_SPONSOR => $this->sponsor?->name ?? 'Sponsor',
            self::PAYER_CORPORATE => $this->corporateClient?->name ?? 'Corporate client',
            default => $this->patient?->full_name ?? 'Patient',
        };
    }

    public function payerBadgeColor(): string
    {
        return match ($this->payer_type) {
            self::PAYER_INSURANCE => 'primary',
            self::PAYER_SPONSOR => 'info',
            self::PAYER_CORPORATE => 'dark',
            default => 'success',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_PARTIALLY_PAID => 'info',
            self::STATUS_OVERDUE => 'danger',
            self::STATUS_WRITTEN_OFF => 'dark',
            self::STATUS_CANCELLED => 'secondary',
            default => 'warning',
        };
    }

    public function markFromBalance(): self
    {
        $balance = round((float) $this->balance, 2);
        $paid = round((float) $this->paid_amount, 2);
        $allocated = round((float) $this->allocated_amount, 2);

        $status = match (true) {
            $allocated <= 0.0 => self::STATUS_CANCELLED,
            $this->invoice && in_array($this->invoice->status?->value ?? $this->invoice->status, [
                InvoiceStatus::CANCELLED->value,
                InvoiceStatus::REFUNDED->value,
            ], true) => self::STATUS_CANCELLED,
            $balance <= 0.0 => self::STATUS_PAID,
            $this->write_off_amount >= $allocated - 0.01 => self::STATUS_WRITTEN_OFF,
            $this->due_date && $this->due_date->isPast() => self::STATUS_OVERDUE,
            $paid > 0.0 || (float) $this->credit_note_amount > 0.0 || (float) $this->write_off_amount > 0.0 => self::STATUS_PARTIALLY_PAID,
            default => self::STATUS_PENDING,
        };

        $this->status = $status;

        return $this;
    }
}
