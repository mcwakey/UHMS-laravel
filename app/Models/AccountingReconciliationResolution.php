<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingReconciliationResolution extends Model
{
    use HasFactory;

    public const TYPES = [
        'retry_posting',
        'reverse_journal',
        'source_corrected',
        'manual_journal_linked',
        'accepted_timing_difference',
        'mapping_corrected',
        'waived_after_review',
        'other',
    ];

    protected $fillable = [
        'accounting_reconciliation_run_id', 'accounting_reconciliation_item_id',
        'resolution_type', 'resolution_note', 'linked_journal_entry_id',
        'linked_posting_attempt_id', 'linked_source_type', 'linked_source_id',
        'resolved_by', 'resolved_at', 'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'metadata_snapshot' => 'array',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AccountingReconciliationRun::class, 'accounting_reconciliation_run_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AccountingReconciliationItem::class, 'accounting_reconciliation_item_id');
    }

    public function linkedJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'linked_journal_entry_id');
    }

    public function linkedPostingAttempt(): BelongsTo
    {
        return $this->belongsTo(AccountingPostingAttempt::class, 'linked_posting_attempt_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
