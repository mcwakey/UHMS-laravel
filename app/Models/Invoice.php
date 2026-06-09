<?php

namespace App\Models;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, GeneratesNumbers, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_amount', 'amount_paid', 'balance', 'billing_type'])
            ->logOnlyDirty()
            ->useLogName('billing')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'invoice_number',
        'visit_id',
        'patient_id',
        'external_party_name',
        'blood_request_id',
        'sponsor_id',
        'corporate_client_id',
        'billing_type',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'adjustment_amount',
        'nhis_amount',
        'total_amount',
        'amount_paid',
        'balance',
        'status',
        'journal_entry_id',
        'accounting_posted_at',
        'accounting_status',
        'accounting_error',
        'due_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'billing_type' => BillingType::class,
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'adjustment_amount' => 'decimal:2',
            'nhis_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
            'accounting_posted_at' => 'datetime',
            'due_date' => 'date',
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

    public function bloodRequest()
    {
        return $this->belongsTo(BloodRequest::class, 'blood_request_id');
    }

    /** Display name for the bill-to party — facility patient or external recipient. */
    public function getBillToNameAttribute(): string
    {
        return $this->patient?->full_name ?: ($this->external_party_name ?: 'Unknown');
    }

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function corporateClient()
    {
        return $this->belongsTo(CorporateClient::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function receivables()
    {
        return $this->hasMany(InvoiceReceivable::class);
    }

    public function claim()
    {
        return $this->hasOne(Claim::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    public function discountEvents()
    {
        return $this->hasMany(InvoiceDiscount::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeByStatus($query, InvoiceStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [
            InvoiceStatus::PENDING->value,
            InvoiceStatus::PARTIALLY_PAID->value,
        ]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedTotalAttribute(): string
    {
        return '₵' . number_format($this->total_amount, 2);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return '₵' . number_format($this->balance, 2);
    }

    public function getFormattedAmountPaidAttribute(): string
    {
        return '₵' . number_format($this->amount_paid, 2);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === InvoiceStatus::PAID;
    }
}
