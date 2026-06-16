<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'pay_period',
        'period_start',
        'period_end',
        'status',
        'accounting_status',
        'journal_entry_id',
        'accounting_posted_at',
        'accounting_error',
        'reversal_journal_entry_id',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'settlement_status',
        'settled_amount',
        'generated_by',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
    ];
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'accounting_posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'settled_amount' => 'decimal:2',
    ];
    public function records(): HasMany { return $this->hasMany(PayrollRecord::class); }
    public function payslips(): HasMany { return $this->hasMany(PayrollPayslip::class); }
    public function settlements(): HasMany { return $this->hasMany(PayrollSettlement::class); }
    public function statutorySettlements(): HasMany { return $this->hasMany(PayrollStatutorySettlement::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function reversalJournalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id'); }
}
