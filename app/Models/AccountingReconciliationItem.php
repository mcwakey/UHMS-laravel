<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingReconciliationItem extends Model
{
    use HasFactory;

    public const CLASSIFICATIONS = [
        'balanced',
        'timing_difference',
        'unposted_source',
        'failed_posting',
        'manual_journal',
        'mapping_issue',
        'source_data_issue',
        'period_cutoff',
        'unknown_difference',
        'not_available',
    ];

    public const RESOLUTION_STATUSES = ['open', 'explained', 'resolved', 'accepted_timing', 'waived'];

    protected $fillable = [
        'accounting_reconciliation_run_id', 'source_type', 'source_id',
        'source_reference', 'source_description', 'gl_account_id',
        'subledger_amount', 'gl_amount', 'difference_amount',
        'classification', 'resolution_status', 'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'subledger_amount' => 'decimal:2',
            'gl_amount' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'metadata_snapshot' => 'array',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AccountingReconciliationRun::class, 'accounting_reconciliation_run_id');
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(AccountingReconciliationResolution::class, 'accounting_reconciliation_item_id');
    }
}
