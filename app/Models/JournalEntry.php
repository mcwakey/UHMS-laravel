<?php

namespace App\Models;

use App\Enums\Accounting\JournalEntryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_number',
        'entry_date',
        'fiscal_year_id',
        'accounting_period_id',
        'reference_number',
        'reference_type',
        'reference_id',
        'source_module',
        'idempotency_key',
        'description',
        'status',
        'posted_at',
        'posted_by',
        'created_by',
        'approved_by',
        'approved_at',
        'reversed_entry_id',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'status' => JournalEntryStatus::class,
            'posted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('line_order')->orderBy('id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_entry_id');
    }

    public function reversalEntries(): HasMany
    {
        return $this->hasMany(self::class, 'reversed_entry_id');
    }

    public function scopeLedgerAffecting(Builder $query): Builder
    {
        return $query->whereIn('status', [
            JournalEntryStatus::POSTED->value,
            JournalEntryStatus::REVERSED->value,
        ]);
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines->sum(fn (JournalEntryLine $line) => (float) $line->debit);
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines->sum(fn (JournalEntryLine $line) => (float) $line->credit);
    }

    public function getIsBalancedAttribute(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.005;
    }
}
