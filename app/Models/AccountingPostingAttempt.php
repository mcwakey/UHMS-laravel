<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPostingAttempt extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'processing', 'posted', 'failed', 'waived', 'resolved', 'reversed'];

    protected $fillable = [
        'source_module',
        'source_type',
        'source_id',
        'posting_type',
        'posting_version',
        'idempotency_key',
        'status',
        'journal_entry_id',
        'reversal_journal_entry_id',
        'attempt_count',
        'first_attempted_at',
        'last_attempted_at',
        'next_retry_at',
        'error_code',
        'error_message',
        'error_context',
        'source_snapshot',
        'posting_snapshot',
        'resolved_by',
        'resolved_at',
        'resolution_type',
        'resolution_note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'source_snapshot' => 'array',
            'posting_snapshot' => 'array',
            'error_context' => 'array',
            'first_attempted_at' => 'datetime',
            'last_attempted_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AccountingPostingAttemptEvent::class)->orderBy('occurred_at')->orderBy('id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeUnresolvedFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
