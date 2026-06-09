<?php

namespace App\Models;

use App\Enums\CreditNoteType;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CreditNote extends Model
{
    use HasFactory, SoftDeletes, GeneratesNumbers, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'status', 'amount', 'reason'])
            ->logOnlyDirty()
            ->useLogName('billing')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'credit_note_number',
        'invoice_id',
        'patient_id',
        'type',
        'status',
        'amount',
        'reason',
        'notes',
        'issued_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'journal_entry_id',
        'reversal_journal_entry_id',
        'accounting_posted_at',
        'accounting_status',
        'accounting_error',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => CreditNoteType::class,
            'amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'accounting_posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'issued');
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }
}
