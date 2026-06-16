<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollSettlement extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'settlement_number',
        'settlement_date',
        'amount',
        'payment_account_id',
        'status',
        'accounting_status',
        'journal_entry_id',
        'reversal_journal_entry_id',
        'posted_at',
        'posted_by',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'accounting_error',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date' => 'date',
            'amount' => 'decimal:2',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }
}
