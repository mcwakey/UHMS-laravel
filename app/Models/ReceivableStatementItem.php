<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivableStatementItem extends Model
{
    protected $fillable = [
        'receivable_statement_run_id', 'source_type', 'source_id', 'transaction_date',
        'description', 'debit_amount', 'credit_amount', 'balance_after', 'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'debit_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(ReceivableStatementRun::class, 'receivable_statement_run_id');
    }
}
